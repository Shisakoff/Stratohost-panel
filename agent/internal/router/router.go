// Package router wires up the agent's HTTP API.
package router

import (
	"encoding/json"
	"log/slog"
	"net/http"
	"runtime"
	"strings"

	"github.com/stratohost/agent/internal/config"
	"github.com/stratohost/agent/internal/server"
	"github.com/stratohost/agent/internal/version"
)

func New(cfg *config.Config, manager *server.Manager, logger *slog.Logger) http.Handler {
	mux := http.NewServeMux()

	mux.HandleFunc("GET /api/system", handleSystem)

	mux.HandleFunc("POST /api/servers/{uuid}", handleCreateServer(manager))
	mux.HandleFunc("GET /api/servers/{uuid}", handleGetServer(manager))
	mux.HandleFunc("POST /api/servers/{uuid}/power", handlePowerServer(manager))
	mux.HandleFunc("DELETE /api/servers/{uuid}", handleDeleteServer(manager))

	return requestLogger(logger, authMiddleware(cfg, mux))
}

// authMiddleware requires "Authorization: Bearer <token_id>.<token>" on
// every request, validated locally against the agent's own config - the
// agent never calls back to the panel to check a token.
func authMiddleware(cfg *config.Config, next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		header := r.Header.Get("Authorization")
		tokenID, token, ok := parseBearer(header)
		if !ok || !cfg.ValidateAuthorization(tokenID, token) {
			writeJSON(w, http.StatusUnauthorized, map[string]string{
				"error": "invalid or missing daemon token",
			})
			return
		}
		next.ServeHTTP(w, r)
	})
}

func parseBearer(header string) (tokenID, token string, ok bool) {
	const prefix = "Bearer "
	if !strings.HasPrefix(header, prefix) {
		return "", "", false
	}
	value := strings.TrimPrefix(header, prefix)
	parts := strings.SplitN(value, ".", 2)
	if len(parts) != 2 || parts[0] == "" || parts[1] == "" {
		return "", "", false
	}
	return parts[0], parts[1], true
}

func requestLogger(logger *slog.Logger, next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		logger.Info("request", "method", r.Method, "path", r.URL.Path, "remote", r.RemoteAddr)
		next.ServeHTTP(w, r)
	})
}

type systemResponse struct {
	Architecture string `json:"architecture"`
	OS           string `json:"os"`
	CPUCount     int    `json:"cpu_count"`
	AgentVersion string `json:"agent_version"`
}

func handleSystem(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, http.StatusOK, systemResponse{
		Architecture: runtime.GOARCH,
		OS:           runtime.GOOS,
		CPUCount:     runtime.NumCPU(),
		AgentVersion: version.Version,
	})
}

func writeJSON(w http.ResponseWriter, status int, body any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(body)
}
