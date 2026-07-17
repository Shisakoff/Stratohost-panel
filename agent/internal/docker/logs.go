package docker

import (
	"context"
	"io"

	"github.com/docker/docker/api/types/container"
)

// WriteStdin attaches to a running container just long enough to write one
// line to its stdin - used to send a game server its configured stop
// command (e.g. "stop" for Minecraft) before falling back to SIGTERM.
// The container must have been created with AttachStdin: true.
func (c *Client) WriteStdin(ctx context.Context, id, line string) error {
	resp, err := c.ContainerAttach(ctx, id, container.AttachOptions{Stream: true, Stdin: true})
	if err != nil {
		return err
	}
	defer resp.Close()

	_, err = resp.Conn.Write([]byte(line + "\n"))
	return err
}

// StreamLogs copies a container's combined stdout/stderr to w until the
// container stops producing output or ctx is cancelled. Containers created
// by this package always run with Tty: true, so the stream is raw text -
// no stdcopy demultiplexing needed.
func (c *Client) StreamLogs(ctx context.Context, id string, w io.Writer) error {
	reader, err := c.ContainerLogs(ctx, id, container.LogsOptions{
		ShowStdout: true,
		ShowStderr: true,
		Follow:     true,
	})
	if err != nil {
		return err
	}
	defer reader.Close()

	_, err = io.Copy(w, reader)
	return err
}

// Wait blocks until the container exits and returns its exit code.
func (c *Client) Wait(ctx context.Context, id string) (int64, error) {
	statusCh, errCh := c.ContainerWait(ctx, id, container.WaitConditionNotRunning)
	select {
	case err := <-errCh:
		return 0, err
	case status := <-statusCh:
		return status.StatusCode, nil
	}
}
