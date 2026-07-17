#!/bin/bash
# Installs and registers the StratoHost agent on a node.
#
# Usage (on the node, after cloning the repo):
#   git clone --depth 1 https://github.com/Shisakoff/Stratohost-panel.git
#   cd Stratohost-panel/installer
#   sudo ./agent-install.sh --panel-url=https://panel.example.com \
#       --node-uuid=<uuid> --token-id=<id> --token=<secret> [--port=8080]
#
# The panel prints this exact command (with real values filled in) when you
# run `php artisan stratohost:node:create` inside the panel container.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AGENT_SRC_DIR="$(cd "${SCRIPT_DIR}/../agent" && pwd)"

# shellcheck source=lib/os-detect.sh
source "${SCRIPT_DIR}/lib/os-detect.sh"

PANEL_URL=""
NODE_UUID=""
TOKEN_ID=""
TOKEN=""
AGENT_PORT="8080"

for arg in "$@"; do
    case "$arg" in
        --panel-url=*) PANEL_URL="${arg#*=}" ;;
        --node-uuid=*) NODE_UUID="${arg#*=}" ;;
        --token-id=*) TOKEN_ID="${arg#*=}" ;;
        --token=*) TOKEN="${arg#*=}" ;;
        --port=*) AGENT_PORT="${arg#*=}" ;;
        *)
            echo "error: unknown argument '${arg}'" >&2
            exit 1
            ;;
    esac
done

if [ -z "$PANEL_URL" ] || [ -z "$NODE_UUID" ] || [ -z "$TOKEN_ID" ] || [ -z "$TOKEN" ]; then
    echo "usage: $0 --panel-url=... --node-uuid=... --token-id=... --token=... [--port=8080]" >&2
    exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "error: must be run as root (sudo ./agent-install.sh ...)." >&2
    exit 1
fi

stratohost::detect_os

echo "==> Installing base dependencies"
apt-get update -y
apt-get install -y --no-install-recommends ca-certificates curl gnupg jq tar

stratohost::install_docker

# Build with a pinned, freshly-downloaded Go toolchain rather than whatever
# (possibly ancient) golang-go package apt ships, so behavior is identical
# on Debian 12 and Ubuntu 24.04 regardless of their default package versions.
GO_BOOTSTRAP_DIR="/usr/local/stratohost-go-bootstrap"
if [ ! -x "${GO_BOOTSTRAP_DIR}/bin/go" ]; then
    echo "==> Downloading a Go toolchain to build the agent"
    GO_VERSION="$(curl -fsSL 'https://go.dev/dl/?mode=json' | jq -r '.[0].version')"

    case "$(dpkg --print-architecture)" in
        amd64) GO_ARCH="amd64" ;;
        arm64) GO_ARCH="arm64" ;;
        *)
            echo "error: unsupported architecture '$(dpkg --print-architecture)'" >&2
            exit 1
            ;;
    esac

    TMP_TARBALL="$(mktemp)"
    curl -fsSL "https://go.dev/dl/${GO_VERSION}.linux-${GO_ARCH}.tar.gz" -o "${TMP_TARBALL}"
    rm -rf "${GO_BOOTSTRAP_DIR}"
    mkdir -p "${GO_BOOTSTRAP_DIR}"
    tar -C "${GO_BOOTSTRAP_DIR}" --strip-components=1 -xzf "${TMP_TARBALL}"
    rm -f "${TMP_TARBALL}"
fi

echo "==> Building the agent binary"
(cd "${AGENT_SRC_DIR}" && "${GO_BOOTSTRAP_DIR}/bin/go" build -o /usr/local/bin/stratohost-agent ./cmd/stratohost-agent)

echo "==> Writing config"
mkdir -p /etc/stratohost/agent
cat > /etc/stratohost/agent/config.yml <<EOF
uuid: "${NODE_UUID}"
panel_url: "${PANEL_URL}"

api:
  host: "0.0.0.0"
  port: ${AGENT_PORT}
  ssl:
    enabled: false
    cert: ""
    key: ""

auth:
  token_id: "${TOKEN_ID}"
  token: "${TOKEN}"

docker:
  socket: "unix:///var/run/docker.sock"
EOF
chmod 600 /etc/stratohost/agent/config.yml

echo "==> Installing systemd service"
cat > /etc/systemd/system/stratohost-agent.service <<'EOF'
[Unit]
Description=StratoHost Agent
After=network-online.target docker.service
Requires=docker.service

[Service]
ExecStart=/usr/local/bin/stratohost-agent --config=/etc/stratohost/agent/config.yml
Restart=on-failure
RestartSec=5
User=root

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now stratohost-agent

echo
echo "==> Done. Agent listening on port ${AGENT_PORT}."
echo "    Check status: systemctl status stratohost-agent"
echo "    Follow logs:  journalctl -u stratohost-agent -f"
echo "    Open port ${AGENT_PORT} (and any game server allocation ports you assign) in your firewall/security group."
