package server

import (
	"regexp"
	"strconv"
)

var templateVarPattern = regexp.MustCompile(`\{\{([A-Za-z0-9_]+)\}\}`)

// resolveStartup expands {{VARIABLE}} placeholders in the egg's startup
// string using the server's environment plus a few values derived from its
// limits/allocations. Unknown placeholders are left as-is rather than
// blanked out, so a typo in an egg is obvious in the logs instead of
// silently producing a broken command.
func resolveStartup(cfg Config) string {
	vars := make(map[string]string, len(cfg.Environment)+3)
	for k, v := range cfg.Environment {
		vars[k] = v
	}

	vars["SERVER_MEMORY"] = strconv.FormatInt(cfg.Limits.MemoryMB, 10)
	if len(cfg.Allocations) > 0 {
		vars["SERVER_IP"] = cfg.Allocations[0].IP
		vars["SERVER_PORT"] = strconv.Itoa(cfg.Allocations[0].Port)
	}

	return templateVarPattern.ReplaceAllStringFunc(cfg.Startup, func(match string) string {
		key := templateVarPattern.FindStringSubmatch(match)[1]
		if v, ok := vars[key]; ok {
			return v
		}
		return match
	})
}
