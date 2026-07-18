package server

import (
	"context"
	"fmt"
	"log/slog"
	"os"
	"strconv"
	"sync"

	dockerpkg "github.com/stratohost/agent/internal/docker"
	"github.com/stratohost/agent/internal/panel"
)

const (
	gracefulStopTimeoutSeconds = 30
	installContainerMemoryMB   = 512
	installContainerCPULimit   = 100
)

type Status string

const (
	StatusInstalling    Status = "installing"
	StatusInstallFailed Status = "install_failed"
	StatusOffline       Status = "offline"
	StatusRunning       Status = "running"
	StatusStopped       Status = "stopped"
)

// Manager owns every server on this node: it persists each server's
// config to disk (the agent has no database of its own) and translates
// power actions / provisioning into docker package calls.
type Manager struct {
	docker  *dockerpkg.Client
	panel   *panel.Client
	dataDir string
	logger  *slog.Logger

	mu         sync.Mutex
	installing map[string]bool
}

func NewManager(docker *dockerpkg.Client, panelClient *panel.Client, dataDir string, logger *slog.Logger) *Manager {
	return &Manager{
		docker:     docker,
		panel:      panelClient,
		dataDir:    dataDir,
		logger:     logger,
		installing: make(map[string]bool),
	}
}

func containerName(uuid string) string {
	return "sh-" + uuid
}

func envMapToSlice(env map[string]string) []string {
	out := make([]string, 0, len(env))
	for k, v := range env {
		out = append(out, k+"="+v)
	}
	return out
}

// Create persists the server's config and kicks off provisioning (install
// script, then optionally starting the container) in the background.
// Returns as soon as the config is safely on disk - the HTTP handler
// responds 202 and the panel finds out the install result later, via
// ReportInstall.
func (m *Manager) Create(cfg Config) error {
	if err := m.saveConfig(cfg); err != nil {
		return err
	}

	if err := os.MkdirAll(m.volumePath(cfg.UUID), 0o755); err != nil {
		return fmt.Errorf("creating volume dir: %w", err)
	}

	m.setInstalling(cfg.UUID, true)
	go m.provision(cfg)

	return nil
}

func (m *Manager) setInstalling(uuid string, v bool) {
	m.mu.Lock()
	defer m.mu.Unlock()
	if v {
		m.installing[uuid] = true
	} else {
		delete(m.installing, uuid)
	}
}

func (m *Manager) isInstalling(uuid string) bool {
	m.mu.Lock()
	defer m.mu.Unlock()
	return m.installing[uuid]
}

func (m *Manager) provision(cfg Config) {
	ctx := context.Background()
	defer m.setInstalling(cfg.UUID, false)

	success := true
	if cfg.Install != nil {
		if err := m.runInstallScript(ctx, cfg); err != nil {
			m.logger.Error("install script failed", "uuid", cfg.UUID, "error", err)
			success = false
		}
	}

	if success {
		_ = m.writeStateMarker(cfg.UUID, "installed")
	} else {
		_ = m.writeStateMarker(cfg.UUID, "install_failed")
	}

	if m.panel != nil {
		if err := m.panel.ReportInstall(ctx, cfg.UUID, success); err != nil {
			m.logger.Error("failed to report install result to panel", "uuid", cfg.UUID, "error", err)
		}
	}

	if !success || !cfg.StartOnCompletion {
		return
	}

	if err := m.createContainer(ctx, cfg); err != nil {
		m.logger.Error("failed to create server container", "uuid", cfg.UUID, "error", err)
		return
	}
	if err := m.docker.StartContainer(ctx, containerName(cfg.UUID)); err != nil {
		m.logger.Error("failed to start server container", "uuid", cfg.UUID, "error", err)
	}
}

func (m *Manager) runInstallScript(ctx context.Context, cfg Config) error {
	install := cfg.Install
	name := "sh-install-" + cfg.UUID

	// Best-effort cleanup of a stale container from a previous failed attempt.
	_ = m.docker.RemoveContainer(ctx, name, true)

	if err := m.docker.EnsureImage(ctx, install.Image); err != nil {
		return fmt.Errorf("pulling install image: %w", err)
	}

	scriptPath := m.volumePath(cfg.UUID) + "/.install.sh"
	if err := os.WriteFile(scriptPath, []byte(install.Script), 0o755); err != nil {
		return fmt.Errorf("writing install script: %w", err)
	}
	defer os.Remove(scriptPath)

	env := envMapToSlice(cfg.Environment)
	env = append(env, "STRATOHOST_SERVER_UUID="+cfg.UUID)

	id, err := m.docker.CreateContainer(ctx, dockerpkg.ContainerSpec{
		Name:       name,
		Image:      install.Image,
		Command:    []string{install.Entrypoint, "/mnt/server/.install.sh"},
		Env:        env,
		WorkingDir: "/mnt/server",
		Binds:      []string{m.volumePath(cfg.UUID) + ":/mnt/server"},
		MemoryMB:   installContainerMemoryMB,
		CPULimit:   installContainerCPULimit,
		Labels:     map[string]string{"stratohost.role": "install", "stratohost.server": cfg.UUID},
	})
	if err != nil {
		return fmt.Errorf("creating install container: %w", err)
	}
	defer m.docker.RemoveContainer(context.Background(), id, true)

	if err := m.docker.StartContainer(ctx, id); err != nil {
		return fmt.Errorf("starting install container: %w", err)
	}

	if logFile, err := os.Create(m.volumePath(cfg.UUID) + "/install.log"); err == nil {
		defer logFile.Close()
		if err := m.docker.StreamLogs(ctx, id, logFile); err != nil {
			m.logger.Warn("streaming install logs failed", "uuid", cfg.UUID, "error", err)
		}
	}

	exitCode, err := m.docker.Wait(ctx, id)
	if err != nil {
		return fmt.Errorf("waiting for install container: %w", err)
	}
	if exitCode != 0 {
		return fmt.Errorf("install script exited with code %d", exitCode)
	}

	return nil
}

func (m *Manager) createContainer(ctx context.Context, cfg Config) error {
	name := containerName(cfg.UUID)

	// Remove any stale container with this name first (e.g. leftover from
	// a crash) - Docker refuses to create over a name collision.
	_ = m.docker.RemoveContainer(ctx, name, true)

	if err := m.docker.EnsureImage(ctx, cfg.Image); err != nil {
		return fmt.Errorf("pulling image: %w", err)
	}

	var ports []dockerpkg.PortMapping
	for _, a := range cfg.Allocations {
		ports = append(ports,
			dockerpkg.PortMapping{HostIP: a.IP, HostPort: strconv.Itoa(a.Port), Proto: "tcp"},
			dockerpkg.PortMapping{HostIP: a.IP, HostPort: strconv.Itoa(a.Port), Proto: "udp"},
		)
	}

	_, err := m.docker.CreateContainer(ctx, dockerpkg.ContainerSpec{
		Name:        name,
		Image:       cfg.Image,
		Command:     []string{"sh", "-c", resolveStartup(cfg)},
		Env:         envMapToSlice(cfg.Environment),
		WorkingDir:  "/home/container",
		Binds:       []string{m.volumePath(cfg.UUID) + ":/home/container"},
		Ports:       ports,
		MemoryMB:    cfg.Limits.MemoryMB,
		SwapMB:      cfg.Limits.SwapMB,
		CPULimit:    cfg.Limits.CPULimit,
		Labels:      map[string]string{"stratohost.role": "server", "stratohost.server": cfg.UUID},
		StopSignal:  "SIGTERM",
		AttachStdin: true,
	})
	return err
}

// Power runs a start/stop/restart/kill action against a server. The
// server must have already been created at least once (Create) since
// that's what persists the config used to (re)create the container.
func (m *Manager) Power(ctx context.Context, uuid, action string) error {
	switch action {
	case "start":
		return m.start(ctx, uuid)
	case "stop":
		return m.stop(ctx, uuid)
	case "restart":
		if err := m.stop(ctx, uuid); err != nil {
			return err
		}
		return m.start(ctx, uuid)
	case "kill":
		return m.docker.KillContainer(ctx, containerName(uuid))
	default:
		return fmt.Errorf("unknown power action %q", action)
	}
}

func (m *Manager) start(ctx context.Context, uuid string) error {
	name := containerName(uuid)

	if _, err := m.docker.InspectContainer(ctx, name); err != nil {
		cfg, loadErr := m.loadConfig(uuid)
		if loadErr != nil {
			return fmt.Errorf("no cached config to (re)create container: %w", loadErr)
		}
		if err := m.createContainer(ctx, cfg); err != nil {
			return err
		}
	}

	return m.docker.StartContainer(ctx, name)
}

func (m *Manager) stop(ctx context.Context, uuid string) error {
	name := containerName(uuid)

	cfg, err := m.loadConfig(uuid)
	if err == nil && cfg.StopCommand != "" {
		// Best-effort graceful stop command first (e.g. "stop" for
		// Minecraft). If the container isn't running or doesn't read
		// stdin this fails harmlessly - StopContainer below still sends
		// SIGTERM, then Docker itself escalates to SIGKILL on timeout.
		_ = m.docker.WriteStdin(ctx, name, cfg.StopCommand)
	}

	return m.docker.StopContainer(ctx, name, gracefulStopTimeoutSeconds)
}

// Delete force-removes the server's container. The on-disk volume and
// cached config are left in place unless purge is requested.
func (m *Manager) Delete(ctx context.Context, uuid string, purge bool) error {
	if err := m.docker.RemoveContainer(ctx, containerName(uuid), true); err != nil {
		return err
	}

	if !purge {
		return nil
	}

	if err := m.deleteConfig(uuid); err != nil {
		return err
	}
	_ = os.Remove(m.markerPath(uuid))
	return os.RemoveAll(m.volumePath(uuid))
}

type StatusResponse struct {
	Status Status `json:"status"`
}

func (m *Manager) Status(ctx context.Context, uuid string) StatusResponse {
	if m.isInstalling(uuid) {
		return StatusResponse{Status: StatusInstalling}
	}

	if info, err := m.docker.InspectContainer(ctx, containerName(uuid)); err == nil {
		if info.State != nil && info.State.Running {
			return StatusResponse{Status: StatusRunning}
		}
		return StatusResponse{Status: StatusStopped}
	}

	// No container right now - tell "install failed" apart from "installed
	// fine, just not started/running currently" using the marker file.
	if state, err := m.readStateMarker(uuid); err == nil && state == "install_failed" {
		return StatusResponse{Status: StatusInstallFailed}
	}

	return StatusResponse{Status: StatusOffline}
}

// Stats reports live resource usage for a running server. A server that
// isn't currently running (stopped, installing, offline) has nothing to
// sample, so this returns the zero value rather than an error - the panel
// treats that the same as "no data yet" instead of surfacing a failure.
func (m *Manager) Stats(ctx context.Context, uuid string) (dockerpkg.Stats, error) {
	info, err := m.docker.InspectContainer(ctx, containerName(uuid))
	if err != nil || info.State == nil || !info.State.Running {
		return dockerpkg.Stats{}, nil
	}

	return m.docker.ContainerStats(ctx, containerName(uuid))
}
