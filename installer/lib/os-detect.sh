#!/bin/bash
# Shared by panel-install.sh and agent-install.sh. Supports exactly two
# targets: Debian 12 (bookworm) and Ubuntu 24.04 (noble). Anything else
# aborts with a clear message instead of limping along on an untested OS.
#
# Usage: source this file, then call:
#   stratohost::detect_os        # sets STRATOHOST_OS_ID / _CODENAME
#   stratohost::install_docker   # installs Docker Engine + compose plugin if missing

set -euo pipefail

stratohost::detect_os() {
    if [ ! -f /etc/os-release ]; then
        echo "error: /etc/os-release not found, cannot detect the OS" >&2
        exit 1
    fi

    # shellcheck source=/dev/null
    . /etc/os-release

    STRATOHOST_OS_ID="${ID:-}"
    STRATOHOST_OS_VERSION="${VERSION_ID:-}"

    case "${STRATOHOST_OS_ID}:${STRATOHOST_OS_VERSION}" in
        debian:12)
            STRATOHOST_OS_CODENAME="bookworm"
            ;;
        ubuntu:24.04)
            STRATOHOST_OS_CODENAME="noble"
            ;;
        *)
            echo "error: unsupported OS '${STRATOHOST_OS_ID} ${STRATOHOST_OS_VERSION}'." >&2
            echo "StratoHost installers only support Debian 12 (bookworm) and Ubuntu 24.04 (noble)." >&2
            exit 1
            ;;
    esac

    export STRATOHOST_OS_ID STRATOHOST_OS_VERSION STRATOHOST_OS_CODENAME
    echo "==> Detected ${STRATOHOST_OS_ID} ${STRATOHOST_OS_VERSION} (${STRATOHOST_OS_CODENAME})"
}

stratohost::install_docker() {
    if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        echo "==> Docker + compose plugin already installed, skipping"
        return 0
    fi

    if [ -z "${STRATOHOST_OS_ID:-}" ]; then
        stratohost::detect_os
    fi

    echo "==> Installing Docker Engine from the official Docker apt repo"

    apt-get update -y
    apt-get install -y --no-install-recommends ca-certificates curl gnupg

    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL "https://download.docker.com/linux/${STRATOHOST_OS_ID}/gpg" \
        -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc

    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/${STRATOHOST_OS_ID} ${STRATOHOST_OS_CODENAME} stable" \
        > /etc/apt/sources.list.d/docker.list

    apt-get update -y
    apt-get install -y --no-install-recommends \
        docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    systemctl enable --now docker

    echo "==> Docker installed: $(docker --version)"
}
