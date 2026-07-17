#!/bin/bash
# Installs the StratoHost panel via Docker Compose.
#
# Usage (on a fresh Debian 12 or Ubuntu 24.04 machine):
#   git clone --depth 1 https://github.com/Shisakoff/Stratohost-panel.git
#   cd Stratohost-panel/installer
#   sudo ./panel-install.sh
#
# Re-running is safe: it won't touch an existing .env or reclone over an
# existing checkout, it just rebuilds and restarts the containers.

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
apt-get install -y --no-install-recommends ca-certificates curl gnupg openssl

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

echo
echo "==> Done. Panel URL: ${APP_URL_VALUE} (container listening on host port ${PORT_VALUE})"
echo "    Put a reverse proxy with TLS (nginx/Caddy) in front of that port for production use."
echo "    Once logged in, use 'php artisan stratohost:node:create' inside the panel container"
echo "    to register a node and get its agent install command."
