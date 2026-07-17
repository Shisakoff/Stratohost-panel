#!/bin/sh
# Runs in a throwaway alpine container with the server's volume mounted at
# /mnt/server (the agent's install flow - see
# agent/internal/server/manager.go:runInstallScript). Plain POSIX sh, not
# bash: alpine doesn't ship bash, and this script is itself what would
# install it, so it can't depend on it.
set -e

apk add --no-cache curl jq >/dev/null

cd /mnt/server

VERSION="${MINECRAFT_VERSION:-latest}"
MANIFEST_URL="https://piston-meta.mojang.com/mc/game/version_manifest_v2.json"
MANIFEST="$(curl -fsSL "$MANIFEST_URL")"

if [ "$VERSION" = "latest" ]; then
    VERSION="$(echo "$MANIFEST" | jq -r '.latest.release')"
fi

echo "Installing Minecraft server ${VERSION}"

VERSION_URL="$(echo "$MANIFEST" | jq -r --arg v "$VERSION" '.versions[] | select(.id == $v) | .url')"
if [ -z "$VERSION_URL" ]; then
    echo "Unknown Minecraft version: ${VERSION}" >&2
    exit 1
fi

SERVER_URL="$(curl -fsSL "$VERSION_URL" | jq -r '.downloads.server.url')"
curl -fsSL -o server.jar "$SERVER_URL"

echo "eula=true" > eula.txt

echo "Install complete: Minecraft ${VERSION} in $(pwd)/server.jar"
