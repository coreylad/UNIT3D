#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root." >&2
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO_URL="https://github.com/WhatCD/Ocelot.git"
REPO_DIR="/opt/ocelot-src"
SERVICE_NAME="ocelot"
CONF_DIR="/etc/ocelot"
CONF_PATH="$CONF_DIR/ocelot.conf"
INSTALL_PREFIX="/usr/local"
RUN_USER="www-data"
RUN_GROUP="www-data"
MYSQL_HOST="127.0.0.1"
MYSQL_USER="unit3d"
MYSQL_PASS="change_me"
MYSQL_DB="unit3d"
LISTEN_PORT="3400"
INSTALL_DEPS="true"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --repo-url)
      REPO_URL="$2"
      shift 2
      ;;
    --repo-dir)
      REPO_DIR="$2"
      shift 2
      ;;
    --service-name)
      SERVICE_NAME="$2"
      shift 2
      ;;
    --run-user)
      RUN_USER="$2"
      shift 2
      ;;
    --run-group)
      RUN_GROUP="$2"
      shift 2
      ;;
    --mysql-host)
      MYSQL_HOST="$2"
      shift 2
      ;;
    --mysql-user)
      MYSQL_USER="$2"
      shift 2
      ;;
    --mysql-pass)
      MYSQL_PASS="$2"
      shift 2
      ;;
    --mysql-db)
      MYSQL_DB="$2"
      shift 2
      ;;
    --listen-port)
      LISTEN_PORT="$2"
      shift 2
      ;;
    --skip-deps)
      INSTALL_DEPS="false"
      shift
      ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
done

if [[ "$INSTALL_DEPS" == "true" ]]; then
  apt-get update
  apt-get install -y \
    git \
    build-essential \
    autoconf \
    automake \
    libtool \
    pkg-config \
    libboost-all-dev \
    libev-dev \
    libmysql++-dev
fi

if [[ -d "$REPO_DIR/.git" ]]; then
  git -C "$REPO_DIR" fetch --all --tags
  git -C "$REPO_DIR" reset --hard origin/master
else
  rm -rf "$REPO_DIR"
  git clone "$REPO_URL" "$REPO_DIR"
fi

cd "$REPO_DIR"

if [[ ! -x "./configure" ]]; then
  autoreconf -fi
fi

./configure --prefix="$INSTALL_PREFIX"
make -j"$(nproc)"
make install

if ! getent group "$RUN_GROUP" >/dev/null 2>&1; then
  groupadd --system "$RUN_GROUP"
fi

if ! getent passwd "$RUN_USER" >/dev/null 2>&1; then
  useradd --system --no-create-home --shell /usr/sbin/nologin --gid "$RUN_GROUP" "$RUN_USER"
fi

install -d -m 0750 "$CONF_DIR"

if [[ ! -f "$CONF_PATH" ]]; then
  install -m 0640 "$ROOT_DIR/scripts/ocelot.conf.dist" "$CONF_PATH"
fi

sed -i "s|^listen_port\s*=.*$|listen_port         = $LISTEN_PORT|" "$CONF_PATH"
sed -i "s|^mysql_host\s*=.*$|mysql_host          = $MYSQL_HOST|" "$CONF_PATH"
sed -i "s|^mysql_username\s*=.*$|mysql_username      = $MYSQL_USER|" "$CONF_PATH"
sed -i "s|^mysql_password\s*=.*$|mysql_password      = $MYSQL_PASS|" "$CONF_PATH"
sed -i "s|^mysql_db\s*=.*$|mysql_db            = $MYSQL_DB|" "$CONF_PATH"

if grep -q '^report_password\s*=\s*00000000000000000000000000000000' "$CONF_PATH"; then
  REPORT_PASSWORD="$(openssl rand -hex 16)"
  sed -i "s|^report_password\s*=.*$|report_password     = $REPORT_PASSWORD|" "$CONF_PATH"
fi

if grep -q '^site_password\s*=\s*00000000000000000000000000000000' "$CONF_PATH"; then
  SITE_PASSWORD="$(openssl rand -hex 16)"
  sed -i "s|^site_password\s*=.*$|site_password       = $SITE_PASSWORD|" "$CONF_PATH"
fi

chown root:"$RUN_GROUP" "$CONF_PATH"
chmod 0640 "$CONF_PATH"

SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=Ocelot BitTorrent Tracker
After=network.target mysql.service mariadb.service

[Service]
Type=simple
User=$RUN_USER
Group=$RUN_GROUP
WorkingDirectory=$CONF_DIR
ExecStart=${INSTALL_PREFIX}/bin/ocelot -c $CONF_PATH
Restart=always
RestartSec=2
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now "${SERVICE_NAME}.service"

echo "Ocelot service installed and started: ${SERVICE_NAME}.service"
echo "Config file: $CONF_PATH"
echo "Service status: systemctl status ${SERVICE_NAME}.service --no-pager"
echo "Logs: journalctl -u ${SERVICE_NAME}.service -f"
