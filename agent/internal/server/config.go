// Package server orchestrates the lifecycle of a single game server on top
// of the docker package: provisioning (running the egg's install script),
// power actions (start/stop/restart/kill), and teardown.
package server

type Limits struct {
	MemoryMB int64 `json:"memory_mb"`
	SwapMB   int64 `json:"swap_mb"`
	DiskMB   int64 `json:"disk_mb"`
	// CPULimit follows the Pterodactyl convention: 100 = 1 full core, 0 = unlimited.
	CPULimit int64 `json:"cpu_limit"`
}

type Allocation struct {
	IP   string `json:"ip"`
	Port int    `json:"port"`
}

type InstallScript struct {
	Image      string `json:"image"`
	Entrypoint string `json:"entrypoint"`
	Script     string `json:"script"`
}

// Config is the full description of a server, as sent by the panel on
// creation and cached to disk so the agent can recreate the container
// (e.g. after a restart, or after the container was removed) without
// having to ask the panel again.
type Config struct {
	UUID              string            `json:"uuid"`
	Image             string            `json:"image"`
	Startup           string            `json:"startup"`
	StopCommand       string            `json:"stop_command"`
	Environment       map[string]string `json:"environment"`
	Limits            Limits            `json:"limits"`
	Allocations       []Allocation      `json:"allocations"`
	Install           *InstallScript    `json:"install"`
	StartOnCompletion bool              `json:"start_on_completion"`
}
