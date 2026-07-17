// Command stratohost-agent is the node daemon: it exposes an HTTP API the
// panel and browsers talk to, and manages the Docker containers backing
// each game server on this node.
package main

import (
	"flag"
	"fmt"
	"log/slog"
	"net/http"
	"os"

	"github.com/stratohost/agent/internal/config"
	"github.com/stratohost/agent/internal/router"
)

func main() {
	configPath := flag.String("config", "config.yml", "path to config.yml")
	flag.Parse()

	logger := slog.New(slog.NewTextHandler(os.Stdout, nil))

	cfg, err := config.Load(*configPath)
	if err != nil {
		logger.Error("failed to load config", "error", err)
		os.Exit(1)
	}

	addr := fmt.Sprintf("%s:%d", cfg.API.Host, cfg.API.Port)
	handler := router.New(cfg, logger)

	logger.Info("stratohost-agent starting", "addr", addr, "node_uuid", cfg.UUID)

	var serveErr error
	if cfg.API.SSL.Enabled {
		serveErr = http.ListenAndServeTLS(addr, cfg.API.SSL.Cert, cfg.API.SSL.Key, handler)
	} else {
		serveErr = http.ListenAndServe(addr, handler)
	}

	if serveErr != nil {
		logger.Error("server stopped", "error", serveErr)
		os.Exit(1)
	}
}
