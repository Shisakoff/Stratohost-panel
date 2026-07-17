package docker

import (
	"context"
	"io"

	"github.com/docker/docker/api/types/image"
)

// EnsureImage pulls ref if it isn't already present locally. Cheap no-op on
// every subsequent server start/restart once the image has been pulled once.
func (c *Client) EnsureImage(ctx context.Context, ref string) error {
	if _, err := c.ImageInspect(ctx, ref); err == nil {
		return nil
	}

	reader, err := c.ImagePull(ctx, ref, image.PullOptions{})
	if err != nil {
		return err
	}
	defer reader.Close()

	// Draining the response is required: ImagePull streams progress events
	// and the pull is not actually complete until EOF is reached.
	_, err = io.Copy(io.Discard, reader)
	return err
}
