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
	dockerpkg "github.com/stratohost/agent/internal/docker"
	"github.com/stratohost/agent/internal/panel"
	"github.com/stratohost/agent/internal/router"
	"github.com/stratohost/agent/internal/server"
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

	dockerClient, err := dockerpkg.New(cfg.Docker.Socket)
	if err != nil {
		logger.Error("failed to create docker client", "error", err)
		os.Exit(1)
	}

	panelClient := panel.New(cfg.PanelURL, cfg.Auth.TokenID, cfg.Auth.Token)
	manager := server.NewManager(dockerClient, panelClient, cfg.DataDir, logger)

	addr := fmt.Sprintf("%s:%d", cfg.API.Host, cfg.API.Port)
	handler := router.New(cfg, manager, logger)

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
