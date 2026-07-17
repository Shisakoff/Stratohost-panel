// Package config loads and validates the agent's config.yml.
package config

import (
	"crypto/subtle"
	"fmt"
	"os"

	"gopkg.in/yaml.v3"
)

type Config struct {
	UUID     string `yaml:"uuid"`
	PanelURL string `yaml:"panel_url"`

	API struct {
		Host string `yaml:"host"`
		Port int    `yaml:"port"`
		SSL  struct {
			Enabled bool   `yaml:"enabled"`
			Cert    string `yaml:"cert"`
			Key     string `yaml:"key"`
		} `yaml:"ssl"`
	} `yaml:"api"`

	Auth struct {
		TokenID string `yaml:"token_id"`
		Token   string `yaml:"token"`
	} `yaml:"auth"`

	Docker struct {
		Socket string `yaml:"socket"`
	} `yaml:"docker"`

	// DataDir holds per-server volumes (<data_dir>/volumes/<uuid>) and
	// cached server configs (<data_dir>/servers/<uuid>.json) - the agent
	// has no database of its own.
	DataDir string `yaml:"data_dir"`
}

func Default() *Config {
	c := &Config{}
	c.API.Host = "0.0.0.0"
	c.API.Port = 8080
	c.Docker.Socket = "unix:///var/run/docker.sock"
	c.DataDir = "/var/lib/stratohost"
	return c
}

func Load(path string) (*Config, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, fmt.Errorf("reading config file: %w", err)
	}

	c := Default()
	if err := yaml.Unmarshal(data, c); err != nil {
		return nil, fmt.Errorf("parsing config file: %w", err)
	}

	if err := c.validate(); err != nil {
		return nil, fmt.Errorf("invalid config: %w", err)
	}

	return c, nil
}

func (c *Config) validate() error {
	if c.UUID == "" {
		return fmt.Errorf("uuid is required")
	}
	if c.PanelURL == "" {
		return fmt.Errorf("panel_url is required")
	}
	if c.Auth.TokenID == "" || c.Auth.Token == "" {
		return fmt.Errorf("auth.token_id and auth.token are required")
	}
	return nil
}

// ValidateAuthorization checks a "<token_id>.<token>" bearer value against
// the configured daemon token, in constant time.
func (c *Config) ValidateAuthorization(tokenID, token string) bool {
	idMatch := subtle.ConstantTimeCompare([]byte(tokenID), []byte(c.Auth.TokenID)) == 1
	tokenMatch := subtle.ConstantTimeCompare([]byte(token), []byte(c.Auth.Token)) == 1
	return idMatch && tokenMatch
}
