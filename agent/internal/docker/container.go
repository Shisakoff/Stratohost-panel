package docker

import (
	"context"

	"github.com/docker/docker/api/types/container"
	"github.com/docker/docker/errdefs"
	"github.com/docker/go-connections/nat"
)

type PortMapping struct {
	HostIP   string
	HostPort string
	Proto    string // "tcp" or "udp", defaults to "tcp"
}

type ContainerSpec struct {
	Name       string
	Image      string
	Command    []string
	Env        []string
	WorkingDir string
	// "host/path:/container/path" style bind mounts.
	Binds []string
	Ports []PortMapping
	// MemoryMB <= 0 means unlimited.
	MemoryMB int64
	SwapMB   int64
	// CPULimit follows the Pterodactyl convention: 100 = 1 full core, 0 = unlimited.
	CPULimit    int64
	Labels      map[string]string
	StopSignal  string
	AttachStdin bool
}

// CreateContainer creates (but does not start) a container. The caller is
// responsible for removing any pre-existing container with the same name
// first - Docker refuses to create over a name collision.
func (c *Client) CreateContainer(ctx context.Context, spec ContainerSpec) (string, error) {
	exposedPorts := nat.PortSet{}
	portBindings := nat.PortMap{}
	for _, p := range spec.Ports {
		proto := p.Proto
		if proto == "" {
			proto = "tcp"
		}
		port, err := nat.NewPort(proto, p.HostPort)
		if err != nil {
			return "", err
		}
		exposedPorts[port] = struct{}{}
		portBindings[port] = append(portBindings[port], nat.PortBinding{
			HostIP:   p.HostIP,
			HostPort: p.HostPort,
		})
	}

	var memoryBytes, swapBytes int64
	if spec.MemoryMB > 0 {
		memoryBytes = spec.MemoryMB * 1024 * 1024
		swapBytes = memoryBytes
		if spec.SwapMB > 0 {
			swapBytes = memoryBytes + spec.SwapMB*1024*1024
		}
	}

	// CPULimit is "100 per core" (Pterodactyl convention); Docker's NanoCPUs
	// is billionths of a core, so 1 unit of CPULimit = 1e9/100 = 1e7 NanoCPUs.
	var nanoCPUs int64
	if spec.CPULimit > 0 {
		nanoCPUs = spec.CPULimit * 10_000_000
	}

	containerConfig := &container.Config{
		Image:        spec.Image,
		Cmd:          spec.Command,
		Env:          spec.Env,
		WorkingDir:   spec.WorkingDir,
		ExposedPorts: exposedPorts,
		Labels:       spec.Labels,
		OpenStdin:    spec.AttachStdin,
		Tty:          true,
		StopSignal:   spec.StopSignal,
	}

	hostConfig := &container.HostConfig{
		Binds:        spec.Binds,
		PortBindings: portBindings,
		Resources: container.Resources{
			Memory:     memoryBytes,
			MemorySwap: swapBytes,
			NanoCPUs:   nanoCPUs,
		},
		RestartPolicy: container.RestartPolicy{Name: "no"},
	}

	resp, err := c.ContainerCreate(ctx, containerConfig, hostConfig, nil, nil, spec.Name)
	if err != nil {
		return "", err
	}

	return resp.ID, nil
}

func (c *Client) StartContainer(ctx context.Context, id string) error {
	return c.ContainerStart(ctx, id, container.StartOptions{})
}

// StopContainer sends SIGTERM (or spec.StopSignal) and waits up to
// timeoutSeconds before Docker escalates to SIGKILL itself.
func (c *Client) StopContainer(ctx context.Context, id string, timeoutSeconds int) error {
	return c.ContainerStop(ctx, id, container.StopOptions{Timeout: &timeoutSeconds})
}

func (c *Client) KillContainer(ctx context.Context, id string) error {
	return c.ContainerKill(ctx, id, "SIGKILL")
}

// RemoveContainer is idempotent: removing a container that doesn't exist
// (already gone, or never created) is not an error.
func (c *Client) RemoveContainer(ctx context.Context, id string, force bool) error {
	err := c.ContainerRemove(ctx, id, container.RemoveOptions{Force: force})
	if err != nil && errdefs.IsNotFound(err) {
		return nil
	}
	return err
}

func (c *Client) InspectContainer(ctx context.Context, id string) (container.InspectResponse, error) {
	return c.ContainerInspect(ctx, id)
}
