// Package panel is the agent's client for calling back to the panel (e.g.
// reporting that an install script finished). It reuses the same daemon
// token the panel uses to call the agent - it's a shared secret between
// the panel and this specific node, valid in both directions.
package panel

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
	"time"
)

type Client struct {
	baseURL    string
	authHeader string
	http       *http.Client
}

func New(baseURL, tokenID, token string) *Client {
	return &Client{
		baseURL:    strings.TrimRight(baseURL, "/"),
		authHeader: fmt.Sprintf("Bearer %s.%s", tokenID, token),
		http:       &http.Client{Timeout: 15 * time.Second},
	}
}

func (c *Client) ReportInstall(ctx context.Context, serverUUID string, successful bool) error {
	body, err := json.Marshal(map[string]bool{"successful": successful})
	if err != nil {
		return err
	}

	url := fmt.Sprintf("%s/api/remote/servers/%s/install", c.baseURL, serverUUID)
	req, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(body))
	if err != nil {
		return err
	}
	req.Header.Set("Authorization", c.authHeader)
	req.Header.Set("Content-Type", "application/json")

	resp, err := c.http.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 300 {
		return fmt.Errorf("panel returned status %d", resp.StatusCode)
	}
	return nil
}
