package docker

import (
	"context"
	"encoding/json"

	"github.com/docker/docker/api/types/container"
)

type Stats struct {
	CPUPercent  float64 `json:"cpu_percent"`
	MemoryUsage int64   `json:"memory_usage_bytes"`
	MemoryLimit int64   `json:"memory_limit_bytes"`
}

// ContainerStats takes one non-streaming sample. Docker's daemon still
// includes a "pre" reading a second apart even for a single-shot request,
// which is enough to compute a CPU percentage the same way `docker stats`
// does - no need to keep our own connection open and sample twice.
func (c *Client) ContainerStats(ctx context.Context, id string) (Stats, error) {
	resp, err := c.ContainerStatsOneShot(ctx, id)
	if err != nil {
		return Stats{}, err
	}
	defer resp.Body.Close()

	var raw container.StatsResponse
	if err := json.NewDecoder(resp.Body).Decode(&raw); err != nil {
		return Stats{}, err
	}

	return Stats{
		CPUPercent:  cpuPercent(raw),
		MemoryUsage: memoryUsage(raw),
		MemoryLimit: int64(raw.MemoryStats.Limit),
	}, nil
}

func cpuPercent(s container.StatsResponse) float64 {
	cpuDelta := float64(s.CPUStats.CPUUsage.TotalUsage) - float64(s.PreCPUStats.CPUUsage.TotalUsage)
	systemDelta := float64(s.CPUStats.SystemUsage) - float64(s.PreCPUStats.SystemUsage)
	if systemDelta <= 0 || cpuDelta <= 0 {
		return 0
	}

	numCPUs := float64(s.CPUStats.OnlineCPUs)
	if numCPUs == 0 {
		numCPUs = float64(len(s.CPUStats.CPUUsage.PercpuUsage))
	}
	if numCPUs == 0 {
		numCPUs = 1
	}

	return (cpuDelta / systemDelta) * numCPUs * 100.0
}

// memoryUsage excludes page cache from the raw usage counter, matching
// what `docker stats` shows rather than the (often much higher) raw
// cgroup figure.
func memoryUsage(s container.StatsResponse) int64 {
	usage := int64(s.MemoryStats.Usage)
	if cache, ok := s.MemoryStats.Stats["cache"]; ok {
		usage -= int64(cache)
	} else if inactiveFile, ok := s.MemoryStats.Stats["inactive_file"]; ok {
		// cgroup v2 exposes this instead of "cache".
		usage -= int64(inactiveFile)
	}
	if usage < 0 {
		usage = 0
	}
	return usage
}
