#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root." >&2
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SERVICE_NAME="ocelot"
CONF_DIR="/etc/ocelot"
REPO_DIR="/opt/ocelot-src"
INSTALL_PREFIX="/usr/local"
PURGE_CONFIG="false"
PURGE_SOURCE="false"
SWITCH_INTERNAL="true"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --service-name)
      SERVICE_NAME="$2"
      shift 2
      ;;
    --config-dir)
      CONF_DIR="$2"
      shift 2
      ;;
    --repo-dir)
      REPO_DIR="$2"
      shift 2
      ;;
    --install-prefix)
      INSTALL_PREFIX="$2"
      shift 2
      ;;
    --purge-config)
      PURGE_CONFIG="true"
      shift
      ;;
    --purge-source)
      PURGE_SOURCE="true"
      shift
      ;;
    --no-switch-internal)
      SWITCH_INTERNAL="false"
      shift
      ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
done

if systemctl list-unit-files | grep -q "^${SERVICE_NAME}\.service"; then
  systemctl disable --now "${SERVICE_NAME}.service" || true
fi

SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
if [[ -f "$SERVICE_FILE" ]]; then
  rm -f "$SERVICE_FILE"
fi

systemctl daemon-reload
systemctl reset-failed

BINARY_PATH="${INSTALL_PREFIX}/bin/ocelot"
if [[ -f "$BINARY_PATH" ]]; then
  rm -f "$BINARY_PATH"
fi

if [[ "$PURGE_CONFIG" == "true" && -d "$CONF_DIR" ]]; then
  rm -rf "$CONF_DIR"
fi

if [[ "$PURGE_SOURCE" == "true" && -d "$REPO_DIR" ]]; then
  rm -rf "$REPO_DIR"
fi

if [[ "$SWITCH_INTERNAL" == "true" && -x "$ROOT_DIR/scripts/ocelot.sh" ]]; then
  bash "$ROOT_DIR/scripts/ocelot.sh" --internal || true
fi

echo "Ocelot service uninstalled."
echo "Removed service: ${SERVICE_NAME}.service"
echo "Removed binary: ${BINARY_PATH}"
if [[ "$SWITCH_INTERNAL" == "true" ]]; then
  echo "Tracker mode switched to internal."
fi
if [[ "$PURGE_CONFIG" == "true" ]]; then
  echo "Removed config directory: ${CONF_DIR}"
fi
if [[ "$PURGE_SOURCE" == "true" ]]; then
  echo "Removed source directory: ${REPO_DIR}"
fi
