// Package docker wraps the Docker Engine SDK with the small set of
// operations the agent needs: pull an image, create/start/stop/kill/remove
// a container, stream its logs, write to its stdin, and wait for exit.
package docker

import (
	"github.com/docker/docker/client"
)

type Client struct {
	*client.Client
}

func New(host string) (*Client, error) {
	cli, err := client.NewClientWithOpts(
		client.WithHost(host),
		client.WithAPIVersionNegotiation(),
	)
	if err != nil {
		return nil, err
	}

	return &Client{cli}, nil
}
