#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$ROOT_DIR/.env"
ENV_EXAMPLE="$ROOT_DIR/.env.example"

MODE="ocelot"
START_CONTAINER="false"
TRACKER_URL=""

extract_host_port_from_url() {
  local url="$1"
  local authority
  local host
  local port

  authority="${url#*://}"
  authority="${authority%%/*}"

  if [[ "$authority" == *":"* ]]; then
    host="${authority%%:*}"
    port="${authority##*:}"
  else
    host="$authority"

    if [[ "$url" == https://* ]]; then
      port="443"
    else
      port="80"
    fi
  fi

  printf '%s;%s\n' "$host" "$port"
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --internal)
      MODE="internal"
      shift
      ;;
    --start-container)
      START_CONTAINER="true"
      shift
      ;;
    *)
      if [[ -z "$TRACKER_URL" ]]; then
        TRACKER_URL="$1"
      else
        echo "Unexpected argument: $1" >&2
        exit 1
      fi
      shift
      ;;
  esac
done

if [[ ! -f "$ENV_FILE" ]]; then
  if [[ ! -f "$ENV_EXAMPLE" ]]; then
    echo ".env and .env.example are both missing." >&2
    exit 1
  fi

  cp "$ENV_EXAMPLE" "$ENV_FILE"
fi

set_env_value() {
  local key="$1"
  local value="$2"
  local tmp_file
  tmp_file="$(mktemp)"

  if grep -q "^${key}=" "$ENV_FILE"; then
    awk -v k="$key" -v v="$value" '
      index($0, k"=") == 1 { print k"="v; next }
      { print }
    ' "$ENV_FILE" > "$tmp_file"
    mv "$tmp_file" "$ENV_FILE"
  else
    printf '%s=%s\n' "$key" "$value" >> "$ENV_FILE"
    rm -f "$tmp_file"
  fi
}

if [[ "$MODE" == "internal" ]]; then
  set_env_value "ANNOUNCE_DRIVER" "internal"
  set_env_value "TRACKER_EXTERNAL_ENABLED" "false"
  set_env_value "TRACKER_HOST" ""
  set_env_value "TRACKER_PORT" ""
  set_env_value "TRACKER_UNIX_SOCKET" ""
  set_env_value "TRACKER_KEY" ""

  echo "Tracker mode set to internal."
else
  if [[ -z "$TRACKER_URL" ]]; then
    TRACKER_URL="http://127.0.0.1:3400/{passkey}/announce"
  fi

  IFS=';' read -r TRACKER_HOST TRACKER_PORT <<< "$(extract_host_port_from_url "$TRACKER_URL")"

  set_env_value "ANNOUNCE_DRIVER" "ocelot"
  set_env_value "OCELOT_ANNOUNCE_URL" "$TRACKER_URL"
  set_env_value "TRACKER_EXTERNAL_ENABLED" "true"
  set_env_value "TRACKER_HOST" "$TRACKER_HOST"
  set_env_value "TRACKER_PORT" "$TRACKER_PORT"
  set_env_value "TRACKER_UNIX_SOCKET" ""
  set_env_value "TRACKER_KEY" ""

  echo "Ocelot tracker configured: $TRACKER_URL"

  if [[ "$START_CONTAINER" == "true" ]]; then
    (
      cd "$ROOT_DIR"
      docker compose -f docker-compose.yml -f docker-compose.ocelot.yml up -d ocelot
    )
  fi
fi

if command -v php >/dev/null 2>&1 && [[ -f "$ROOT_DIR/artisan" ]]; then
  (
    cd "$ROOT_DIR"
    php artisan optimize:clear || true
  )
fi

echo "Done. Re-download .torrent files so clients get the current announce URL."