#!/usr/bin/env bash
# =============================================================================
# TSSE8 → UNIT3D Full Migration Script (Complete Version)
# =============================================================================
# Runs the migrate:tsse8 Artisan command via the correct PHP binary.
# Safe to call from Plesk scheduled tasks or cron.
#
# Usage:
#   ./migrate-tsse8.sh [--memory=512M] [--page-size=50] [--dry-run] [--tables=TABLES]
#
# Examples:
#   ./migrate-tsse8.sh                                    # Full migration, 256M RAM, batch 100
#   ./migrate-tsse8.sh --memory=512M                      # 512M RAM limit
#   ./migrate-tsse8.sh --page-size=50 --tables=torrents   # Torrents only, smaller batches
#   ./migrate-tsse8.sh --dry-run --tables=users           # Test users migration
#
# IMPORTANT: Edit CONFIGURATION section below before first run!
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURATION — EDIT THESE FOR YOUR ENVIRONMENT
# =============================================================================

# Path to PHP CLI (adjust for your host)
PHP_BIN="/opt/plesk/php/8.4/bin/php"

# TSSE8 source database credentials
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="tsse2"
DB_USER="unit3d"
DB_PASS="Honeythecat123"

# Default migration settings
TABLES_DEFAULT="users,torrents,peers,snatched,comments,forums,forum_threads,forum_posts"
PAGE_SIZE_DEFAULT="100"
OFFSET_DEFAULT="0"
MEMORY_LIMIT_DEFAULT="256M"

# =============================================================================
# ARGUMENT PARSING
# =============================================================================

# Defaults
TABLES="$TABLES_DEFAULT"
PAGE_SIZE="$PAGE_SIZE_DEFAULT"
OFFSET="$OFFSET_DEFAULT"
MEMORY_LIMIT="$MEMORY_LIMIT_DEFAULT"
DRY_RUN=0

for arg in "$@"; do
    case "$arg" in
        --memory=*)
            MEMORY_LIMIT="${arg#--memory=}"
            ;;
        --page-size=*)
            PAGE_SIZE="${arg#--page-size=}"
            ;;
        --offset=*)
            OFFSET="${arg#--offset=}"
            ;;
        --tables=*)
            TABLES="${arg#--tables=}"
            ;;
        --dry-run)
            DRY_RUN=1
            ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "OPTIONS:"
            echo "  --memory=SIZE       PHP memory limit (default: 256M)"
            echo "  --page-size=NUM     Batch size per iteration (default: 100)"
            echo "  --offset=NUM        Starting offset for pagination (default: 0)"
            echo "  --tables=LIST       Comma-separated tables to migrate"
            echo "  --dry-run           Test run without writing data"
            echo "  --help              Show this help message"
            echo ""
            echo "EXAMPLES:"
            echo "  $0 --memory=512M --tables=torrents"
            echo "  $0 --page-size=50 --dry-run"
            echo ""
            exit 0
            ;;
        *)
            echo "Unknown argument: $arg" >&2
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# =============================================================================
# VERIFY ENVIRONMENT
# =============================================================================

ARTISAN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ ! -f "$ARTISAN_DIR/artisan" ]]; then
    echo "Error: artisan file not found at $ARTISAN_DIR/artisan" >&2
    exit 1
fi

if ! command -v "$PHP_BIN" &> /dev/null; then
    echo "Error: PHP binary not found at $PHP_BIN" >&2
    exit 1
fi

# =============================================================================
# BUILD AND EXECUTE COMMAND
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

if [[ "$DRY_RUN" -eq 1 ]]; then
    CMD+=("--dry-run")
fi

# Print execution info
echo "========================================================"
echo "  TSSE8 → UNIT3D Migration"
echo "========================================================"
echo "Source Host  : ${DB_HOST}:${DB_PORT}"
echo "Source DB    : ${DB_NAME}"
echo "Tables       : ${TABLES}"
echo "Batch Size   : ${PAGE_SIZE}"
echo "Start Offset : ${OFFSET}"
echo "PHP Memory   : ${MEMORY_LIMIT}"
if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "Mode         : DRY RUN (no writes)"
else
    echo "Mode         : LIVE (writes enabled)"
fi
echo "========================================================"
echo ""

# Execute
exec "${CMD[@]}"
