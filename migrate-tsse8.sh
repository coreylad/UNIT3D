#!/usr/bin/env bash
# =============================================================================
# TSSE8 → UNIT3D Full Migration Script
# =============================================================================
# Runs the migrate:tsse8 Artisan command via the correct PHP binary.
# Safe to call from Plesk scheduled tasks or cron — no line-continuation
# issues since all arguments are passed as discrete shell words.
#
# Usage:
#   ./migrate-tsse8.sh [--dry-run] [--tables=users,torrents,...]
#
# Edit the variables in the CONFIGURATION section before running.
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURATION — adjust these before running
# =============================================================================

PHP_BIN="/opt/plesk/php/8.4/bin/php"
ARTISAN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="tsse2"
DB_USER="unit3d"
DB_PASS="Honeythecat123"

# Comma-separated list of stages to run (in order):
#   users,torrents,peers,snatched,comments,forums,forum_threads,forum_posts
TABLES="users,torrents,peers,snatched,comments,forums,forum_threads,forum_posts"

# Batch size per page (lower = less memory, slower)
# Default 100 recommended; reduce to 50 for shared/low-memory hosts
PAGE_SIZE="100"

# Starting offset (usually 0; set higher to resume a partial run)
OFFSET="0"

# PHP memory limit (default 256M; increase for very large datasets)
# Expressed as: integer bytes, or with suffix K, M, G (e.g. "512M")
MEMORY_LIMIT="256M"

# Optional JSON group map: source_group_id -> unit3d_group_id
# Example: GROUP_MAP='{"1":5,"2":3,"3":4}'
# Leave empty to use interactive mapping in the service
GROUP_MAP=""

# =============================================================================
# ARGUMENT PARSING — supports --dry-run and --tables= overrides from CLI
# =============================================================================

DRY_RUN=0
EXTRA_ARGS=()

for arg in "$@"; do
    case "$arg" in
        --dry-run)
            DRY_RUN=1
            ;;
        --tables=*)
            TABLES="${arg#--tables=}"
            ;;
        --page-size=*)
            PAGE_SIZE="${arg#--page-size=}"
            ;;
        --offset=*)
            OFFSET="${arg#--offset=}"
            ;;
        --memory=*)
            MEMORY_LIMIT="${arg#--memory=}"
            ;;
        *)
            echo "Unknown argument: $arg" >&2
            exit 1
            ;;
    esac
done

# =============================================================================
# BUILD COMMAND
# =============================================================================

CMD=(
    "$PHP_BIN"
    "-d" "memory_limit=${MEMORY_LIMIT}"
    "${ARTISAN_DIR}/artisan"
    "migrate:tsse8"
    "--host=${DB_HOST}"
    "--port=${DB_PORT}"
    "--database=${DB_NAME}"
    "--username=${DB_USER}"
    "--password=${DB_PASS}"
    "--tables=${TABLES}"
    "--page-size=${PAGE_SIZE}"
    "--offset=${OFFSET}"
)

if [[ -n "$GROUP_MAP" ]]; then
    CMD+=("--group-map=${GROUP_MAP}")
fi

if [[ "$DRY_RUN" -eq 1 ]]; then
    CMD+=("--dry-run")
fi

# =============================================================================
# RUN
# =============================================================================

echo "========================================================"
echo "  TSSE8 → UNIT3D Migration"
echo "  Host   : ${DB_HOST}:${DB_PORT}"
echo "  Source : ${DB_NAME}"
echo "  Tables : ${TABLES}"
echo "  Offset : ${OFFSET}  |  Page size: ${PAGE_SIZE}"
echo "  Memory : ${MEMORY_LIMIT}"
if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "  Mode   : DRY RUN (no writes)"
else
    echo "  Mode   : LIVE"
fi
echo "========================================================"
echo ""

"${CMD[@]}"
