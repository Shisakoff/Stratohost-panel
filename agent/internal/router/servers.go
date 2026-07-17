package router

import (
	"encoding/json"
	"net/http"

	"github.com/stratohost/agent/internal/server"
)

type createServerRequest struct {
	Image             string                `json:"image"`
	Startup           string                `json:"startup"`
	StopCommand       string                `json:"stop_command"`
	Environment       map[string]string     `json:"environment"`
	Limits            server.Limits         `json:"limits"`
	Allocations       []server.Allocation   `json:"allocations"`
	Install           *server.InstallScript `json:"install"`
	StartOnCompletion bool                  `json:"start_on_completion"`
}

func handleCreateServer(manager *server.Manager) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uuid := r.PathValue("uuid")

		var body createServerRequest
		if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
			writeJSON(w, http.StatusBadRequest, map[string]string{"error": "invalid JSON body"})
			return
		}

		cfg := server.Config{
			UUID:              uuid,
			Image:             body.Image,
			Startup:           body.Startup,
			StopCommand:       body.StopCommand,
			Environment:       body.Environment,
			Limits:            body.Limits,
			Allocations:       body.Allocations,
			Install:           body.Install,
			StartOnCompletion: body.StartOnCompletion,
		}

		if err := manager.Create(cfg); err != nil {
			writeJSON(w, http.StatusInternalServerError, map[string]string{"error": err.Error()})
			return
		}

		writeJSON(w, http.StatusAccepted, map[string]string{"status": "provisioning"})
	}
}

type powerRequest struct {
	Action string `json:"action"`
}

func handlePowerServer(manager *server.Manager) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uuid := r.PathValue("uuid")

		var body powerRequest
		if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
			writeJSON(w, http.StatusBadRequest, map[string]string{"error": "invalid JSON body"})
			return
		}

		if err := manager.Power(r.Context(), uuid, body.Action); err != nil {
			writeJSON(w, http.StatusInternalServerError, map[string]string{"error": err.Error()})
			return
		}

		w.WriteHeader(http.StatusNoContent)
	}
}

func handleDeleteServer(manager *server.Manager) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uuid := r.PathValue("uuid")
		purge := r.URL.Query().Get("purge") == "true"

		if err := manager.Delete(r.Context(), uuid, purge); err != nil {
			writeJSON(w, http.StatusInternalServerError, map[string]string{"error": err.Error()})
			return
		}

		w.WriteHeader(http.StatusNoContent)
	}
}

func handleGetServer(manager *server.Manager) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uuid := r.PathValue("uuid")
		writeJSON(w, http.StatusOK, manager.Status(r.Context(), uuid))
	}
}
