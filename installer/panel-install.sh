#!/bin/bash
# Installs the StratoHost panel via Docker Compose, and - by default -
# also registers this same machine as a node and installs the agent on it,
# so a single run gets you a working panel with one game-server-ready node
# and no manual token copy-pasting. Say no at the prompt if this machine is
# meant to be a panel-only box, and register separate nodes later with
# `docker compose exec panel php artisan stratohost:node:create` +
# agent-install.sh on each of them.
#
# Usage (on a fresh Debian 12 or Ubuntu 24.04 machine):
#   git clone --depth 1 https://github.com/Shisakoff/Stratohost-panel.git
#   cd Stratohost-panel/installer
#   sudo ./panel-install.sh
#
# Re-running is safe: it won't touch an existing .env or reclone over an
# existing checkout, it just rebuilds and restarts the containers. It also
# won't re-register a node or reinstall the agent if one is already
# configured on this machine.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PANEL_DIR="$(cd "${SCRIPT_DIR}/../panel" && pwd)"

# shellcheck source=lib/os-detect.sh
source "${SCRIPT_DIR}/lib/os-detect.sh"

if [ "$(id -u)" -ne 0 ]; then
    echo "error: must be run as root (sudo ./panel-install.sh)." >&2
    exit 1
fi

stratohost::detect_os

echo "==> Installing base dependencies"
apt-get update -y
apt-get install -y --no-install-recommends ca-certificates curl gnupg openssl jq

stratohost::install_docker

cd "${PANEL_DIR}"

if [ ! -f .env ]; then
    echo "==> Generating .env"
    cp .env.example .env

    APP_KEY="base64:$(openssl rand -base64 32)"
    DB_PASSWORD="$(openssl rand -hex 24)"
    MYSQL_ROOT_PASSWORD="$(openssl rand -hex 24)"

    read -rp "Panel URL (e.g. https://panel.example.com) [http://localhost]: " APP_URL < /dev/tty
    APP_URL="${APP_URL:-http://localhost}"
    read -rp "HTTP port to expose the panel on [8000]: " PANEL_HTTP_PORT < /dev/tty
    PANEL_HTTP_PORT="${PANEL_HTTP_PORT:-8000}"

    sed -i "s#^APP_KEY=.*#APP_KEY=${APP_KEY}#" .env
    sed -i "s#^APP_URL=.*#APP_URL=${APP_URL}#" .env
    sed -i "s#^DB_PASSWORD=.*#DB_PASSWORD=${DB_PASSWORD}#" .env
    sed -i "s#^MYSQL_ROOT_PASSWORD=.*#MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}#" .env
    echo "PANEL_HTTP_PORT=${PANEL_HTTP_PORT}" >> .env
else
    echo "==> .env already exists, leaving it untouched"
fi

echo "==> Building and starting containers (first run can take a few minutes)"
docker compose build
docker compose up -d

echo "==> Waiting for the database to be ready"
until docker compose exec -T db healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; do
    sleep 2
done

# Only prompt for an admin account the first time - re-running the script
# after the panel is already set up shouldn't force this again.
if ! docker compose exec -T panel php artisan tinker --execute='exit(\App\Models\User::where("root_admin", true)->exists() ? 1 : 0);' >/dev/null 2>&1; then
    echo "==> Create the first admin account"
    docker compose exec panel php artisan stratohost:user:create-admin < /dev/tty
fi

APP_URL_VALUE="$(grep '^APP_URL=' .env | cut -d= -f2-)"
PORT_VALUE="$(grep '^PANEL_HTTP_PORT=' .env | cut -d= -f2-)"

AGENT_CONFIG="/etc/stratohost/agent/config.yml"
if [ -f "${AGENT_CONFIG}" ]; then
    echo "==> This machine is already registered as a node (${AGENT_CONFIG} exists), skipping."
else
    read -rp "Also set up this VPS as a node and install the agent now? [Y/n]: " SETUP_LOCAL_NODE < /dev/tty
    SETUP_LOCAL_NODE="${SETUP_LOCAL_NODE:-Y}"

    if [[ "$SETUP_LOCAL_NODE" =~ ^[Yy]$ ]]; then
        echo "==> Registering this VPS as a node"

        # Same host the panel itself is reachable on, since that's what the
        # panel container will use to reach back out to the agent.
        NODE_SCHEME="$(echo "$APP_URL_VALUE" | sed -E 's#^(https?)://.*#\1#')"
        NODE_FQDN="$(echo "$APP_URL_VALUE" | sed -E 's#^[a-z]+://##; s#:[0-9]+$##; s#/$##')"

        # Leave headroom for the OS, the panel's own containers, and Docker
        # itself rather than offering 100% of the box to game servers.
        TOTAL_MEM_MB="$(free -m | awk '/^Mem:/{print $2}')"
        NODE_MEMORY_MB=$(((TOTAL_MEM_MB * 70) / 100))
        TOTAL_DISK_MB="$(df --output=avail -m "${PANEL_DIR}" | tail -1 | tr -d ' ')"
        NODE_DISK_MB=$(((TOTAL_DISK_MB * 70) / 100))

        NODE_JSON="$(docker compose exec -T panel php artisan stratohost:node:create \
            --name="$(hostname)" \
            --fqdn="${NODE_FQDN}" \
            --scheme="${NODE_SCHEME}" \
            --port=8080 \
            --memory="${NODE_MEMORY_MB}" \
            --disk="${NODE_DISK_MB}" \
            --json)"

        NODE_UUID="$(echo "${NODE_JSON}" | jq -r '.node_uuid')"
        NODE_TOKEN_ID="$(echo "${NODE_JSON}" | jq -r '.token_id')"
        NODE_TOKEN="$(echo "${NODE_JSON}" | jq -r '.token')"

        echo "==> Installing the agent on this VPS"
        "${SCRIPT_DIR}/agent-install.sh" \
            --panel-url="${APP_URL_VALUE}" \
            --node-uuid="${NODE_UUID}" \
            --token-id="${NODE_TOKEN_ID}" \
            --token="${NODE_TOKEN}" \
            --port=8080
    fi
fi

echo
echo "==> Done. Panel URL: ${APP_URL_VALUE} (container listening on host port ${PORT_VALUE})"
echo "    Put a reverse proxy with TLS (nginx/Caddy) in front of that port for production use."
if [ ! -f "${AGENT_CONFIG}" ]; then
    echo "    Once logged in, use 'php artisan stratohost:node:create' inside the panel container"
    echo "    to register a node and get its agent install command."
fi
