package server

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
)

// configPath returns where a server's Config is cached on disk, e.g.
// <dataDir>/servers/<uuid>.json.
func (m *Manager) configPath(uuid string) string {
	return filepath.Join(m.dataDir, "servers", uuid+".json")
}

// volumePath returns the server's data directory, bind-mounted into its
// container at /home/container.
func (m *Manager) volumePath(uuid string) string {
	return filepath.Join(m.dataDir, "volumes", uuid)
}

func (m *Manager) saveConfig(cfg Config) error {
	if err := os.MkdirAll(filepath.Dir(m.configPath(cfg.UUID)), 0o755); err != nil {
		return fmt.Errorf("creating config dir: %w", err)
	}

	data, err := json.MarshalIndent(cfg, "", "  ")
	if err != nil {
		return fmt.Errorf("encoding config: %w", err)
	}

	return os.WriteFile(m.configPath(cfg.UUID), data, 0o600)
}

func (m *Manager) loadConfig(uuid string) (Config, error) {
	data, err := os.ReadFile(m.configPath(uuid))
	if err != nil {
		return Config{}, fmt.Errorf("reading config: %w", err)
	}

	var cfg Config
	if err := json.Unmarshal(data, &cfg); err != nil {
		return Config{}, fmt.Errorf("decoding config: %w", err)
	}

	return cfg, nil
}

func (m *Manager) deleteConfig(uuid string) error {
	err := os.Remove(m.configPath(uuid))
	if err != nil && !os.IsNotExist(err) {
		return err
	}
	return nil
}

// markerPath tracks the outcome of the last install, so Status() can tell
// "install failed, no container" apart from "installed fine, just not
// running right now" once the container itself is gone or was never
// started (start_on_completion: false).
func (m *Manager) markerPath(uuid string) string {
	return filepath.Join(m.dataDir, "servers", uuid+".state")
}

func (m *Manager) writeStateMarker(uuid, state string) error {
	return os.WriteFile(m.markerPath(uuid), []byte(state), 0o600)
}

func (m *Manager) readStateMarker(uuid string) (string, error) {
	data, err := os.ReadFile(m.markerPath(uuid))
	if err != nil {
		return "", err
	}
	return string(data), nil
}
