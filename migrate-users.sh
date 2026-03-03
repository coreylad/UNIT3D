#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# ═══════════════════════════════════════════════════════════════════════════════
# TSSE → UNIT3D User Migration Script
# ═══════════════════════════════════════════════════════════════════════════════
#
# Migrates users from a TSSE (The Source Software Edition) tracker database
# into UNIT3D. Preserves passkeys, sets up the legacy password bridge,
# creates related records (user_settings, user_privacy, user_notifications),
# and interactively prompts for unmapped group assignments.
#
# Usage:
#   ./migrate-users.sh [--dry-run]
#
# The script will prompt for all database credentials interactively.
# ═══════════════════════════════════════════════════════════════════════════════

# ── Globals ──────────────────────────────────────────────────────────────────

DRY_RUN=0
LOG_FILE="migration_$(date +%Y%m%d_%H%M%S).log"
CHUNK_SIZE=500
MIGRATED=0
UPDATED=0
SKIPPED=0
ERRORS=0

# Associative arrays for group mapping
declare -A GROUP_MAP          # source_group_id => unit3d_group_id
declare -A UNIT3D_GROUPS      # unit3d_group_id => group_name
declare -A SOURCE_GROUPS      # source_group_id => group_name

# ── Helpers ──────────────────────────────────────────────────────────────────

log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "$msg" | tee -a "$LOG_FILE"
}

log_error() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1"
    echo "$msg" | tee -a "$LOG_FILE" >&2
}

die() {
    log_error "$1"
    exit 1
}

# Execute a query against the source database (read-only)
src_query() {
    mysql -h "$SRC_HOST" -P "$SRC_PORT" -u "$SRC_USER" -p"$SRC_PASS" \
        "$SRC_DB" -N -B -e "$1" 2>>"$LOG_FILE"
}

# Execute a query against the UNIT3D destination database
dst_query() {
    mysql -h "$DST_HOST" -P "$DST_PORT" -u "$DST_USER" -p"$DST_PASS" \
        "$DST_DB" -N -B -e "$1" 2>>"$LOG_FILE"
}

# Execute a query and return raw (preserving tabs for column separation)
dst_query_raw() {
    mysql -h "$DST_HOST" -P "$DST_PORT" -u "$DST_USER" -p"$DST_PASS" \
        "$DST_DB" -N -B --raw -e "$1" 2>>"$LOG_FILE"
}

# Escape a string for safe use in SQL single quotes
sql_escape() {
    local val="$1"
    # Escape backslashes first, then single quotes
    val="${val//\\/\\\\}"
    val="${val//\'/\\\'}"
    printf '%s' "$val"
}

# Convert a TSSE date value (unix timestamp, datetime string, or empty) to MySQL datetime
parse_date() {
    local val="$1"
    if [[ -z "$val" || "$val" == "NULL" || "$val" == "0" || "$val" == "0000-00-00 00:00:00" ]]; then
        echo "NULL"
        return
    fi
    # If purely numeric, treat as unix timestamp
    if [[ "$val" =~ ^[0-9]+$ ]]; then
        if [[ "$val" -gt 0 ]]; then
            echo "'$(date -d "@$val" '+%Y-%m-%d %H:%M:%S' 2>/dev/null || echo "NULL")'"
        else
            echo "NULL"
        fi
    else
        # Already a datetime string — validate it
        if date -d "$val" '+%Y-%m-%d %H:%M:%S' &>/dev/null; then
            echo "'$(date -d "$val" '+%Y-%m-%d %H:%M:%S')'"
        else
            echo "NULL"
        fi
    fi
}

# Generate a random 32-char hex string
random_hex() {
    openssl rand -hex 16 2>/dev/null || cat /dev/urandom | tr -dc 'a-f0-9' | head -c 32
}

# ── Parse Arguments ──────────────────────────────────────────────────────────

for arg in "$@"; do
    case "$arg" in
        --dry-run)
            DRY_RUN=1
            ;;
        --help|-h)
            echo "Usage: $0 [--dry-run]"
            echo ""
            echo "Migrates users from a TSSE tracker database to UNIT3D."
            echo ""
            echo "Options:"
            echo "  --dry-run    Simulate migration without writing to the destination database"
            echo "  --help       Show this help message"
            exit 0
            ;;
    esac
done

# ── Credential Collection ────────────────────────────────────────────────────

echo "═══════════════════════════════════════════════════════════════"
echo "  TSSE → UNIT3D User Migration"
if [[ $DRY_RUN -eq 1 ]]; then
    echo "  *** DRY RUN MODE — no writes will be performed ***"
fi
echo "═══════════════════════════════════════════════════════════════"
echo ""

echo "── Source Database (TSSE) ──"
read -rp "  Host [localhost]: " SRC_HOST
SRC_HOST="${SRC_HOST:-localhost}"
read -rp "  Port [3306]: " SRC_PORT
SRC_PORT="${SRC_PORT:-3306}"
read -rp "  Database name: " SRC_DB
[[ -z "$SRC_DB" ]] && die "Source database name is required"
read -rp "  Username: " SRC_USER
[[ -z "$SRC_USER" ]] && die "Source username is required"
read -rsp "  Password: " SRC_PASS
echo ""
[[ -z "$SRC_PASS" ]] && die "Source password is required"

echo ""
echo "── Destination Database (UNIT3D) ──"
read -rp "  Host [localhost]: " DST_HOST
DST_HOST="${DST_HOST:-localhost}"
read -rp "  Port [3306]: " DST_PORT
DST_PORT="${DST_PORT:-3306}"
read -rp "  Database name: " DST_DB
[[ -z "$DST_DB" ]] && die "Destination database name is required"
read -rp "  Username: " DST_USER
[[ -z "$DST_USER" ]] && die "Destination username is required"
read -rsp "  Password: " DST_PASS
echo ""
[[ -z "$DST_PASS" ]] && die "Destination password is required"

echo ""
log "Migration started. Log file: $LOG_FILE"

# ── Pre-flight Checks ────────────────────────────────────────────────────────

log "Running pre-flight checks..."

# Test source connection
src_query "SELECT 1" >/dev/null 2>&1 || die "Cannot connect to source database"
log "  ✓ Source database connected"

# Test destination connection
dst_query "SELECT 1" >/dev/null 2>&1 || die "Cannot connect to destination database"
log "  ✓ Destination database connected"

# Check source users table exists
SRC_USER_COUNT=$(src_query "SELECT COUNT(*) FROM users" 2>/dev/null) || die "Source 'users' table not found"
log "  ✓ Source users table found ($SRC_USER_COUNT rows)"

# Check UNIT3D users table exists
DST_USER_COUNT=$(dst_query "SELECT COUNT(*) FROM users" 2>/dev/null) || die "Destination 'users' table not found"
log "  ✓ Destination users table found ($DST_USER_COUNT existing rows)"

# Detect source columns for smart field mapping
SRC_COLUMNS=$(src_query "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$SRC_DB' AND TABLE_NAME='users'" 2>/dev/null | tr '\n' ' ')
log "  Source user columns: $SRC_COLUMNS"

# Check required destination columns exist (legacy auth bridge)
DST_HAS_LEGACY=$(dst_query "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$DST_DB' AND TABLE_NAME='users' AND COLUMN_NAME='legacy_passhash'" 2>/dev/null)
if [[ "$DST_HAS_LEGACY" -eq 0 ]]; then
    die "Destination 'users' table is missing 'legacy_passhash' column. Run migrations first: php artisan migrate"
fi
log "  ✓ Legacy auth columns present in destination"

# Helper to check if source has a column
has_src_col() {
    [[ " $SRC_COLUMNS " == *" $1 "* ]]
}

# Detect group column in source
GROUP_COL=""
for col in usergroup group_id class rank role_id permission_id user_class level; do
    if has_src_col "$col"; then
        GROUP_COL="$col"
        break
    fi
done
log "  Source group column: ${GROUP_COL:-(none detected, will use fallback)}"

# ── Group Mapping ────────────────────────────────────────────────────────────

log "Building group mapping..."

# Load UNIT3D groups
while IFS=$'\t' read -r gid gname gslug; do
    UNIT3D_GROUPS[$gid]="$gname"
done < <(dst_query "SELECT id, name, slug FROM \`groups\` ORDER BY id")

log "  UNIT3D groups:"
for gid in $(echo "${!UNIT3D_GROUPS[@]}" | tr ' ' '\n' | sort -n); do
    log "    #$gid  ${UNIT3D_GROUPS[$gid]}"
done

# Detect source groups table
SRC_GROUPS_TABLE=""
for tbl in usergroups groups user_groups member_groups permissions ranks classes; do
    if src_query "SELECT 1 FROM \`$tbl\` LIMIT 1" &>/dev/null; then
        SRC_GROUPS_TABLE="$tbl"
        break
    fi
done

if [[ -n "$SRC_GROUPS_TABLE" ]]; then
    log "  Source groups table: $SRC_GROUPS_TABLE"

    # Read source groups
    while IFS=$'\t' read -r sgid sgname; do
        SOURCE_GROUPS[$sgid]="$sgname"
    done < <(src_query "SELECT COALESCE(id, group_id, gid, class_id), COALESCE(name, group_name, title, class_name) FROM \`$SRC_GROUPS_TABLE\`" 2>/dev/null || true)
else
    log "  No source groups table found"
    # If we have a numeric class column, build synthetic group names
    if [[ -n "$GROUP_COL" ]]; then
        while IFS=$'\t' read -r classval; do
            SOURCE_GROUPS[$classval]="Class $classval"
        done < <(src_query "SELECT DISTINCT \`$GROUP_COL\` FROM users WHERE \`$GROUP_COL\` IS NOT NULL ORDER BY \`$GROUP_COL\`")
    fi
fi

# Auto-map source groups to UNIT3D groups using keyword matching
auto_map_group() {
    local src_name="$1"
    local lower
    lower=$(echo "$src_name" | tr '[:upper:]' '[:lower:]')

    # Keyword → UNIT3D group_id lookup
    # Ordered most-specific first
    case "$lower" in
        *sysop*|*"sys op"*|*root*|*siteop*)
            echo "$(find_unit3d_group_by_slug 'administrator')" ;;
        *owner*|*founder*|*"co-owner"*)
            echo "$(find_unit3d_group_by_slug 'owner')" ;;
        *"super admin"*|*superadmin*)
            echo "$(find_unit3d_group_by_slug 'administrator')" ;;
        *admin*|*administrator*)
            echo "$(find_unit3d_group_by_slug 'administrator')" ;;
        *"torrent mod"*|*torrentmod*)
            echo "$(find_unit3d_group_by_slug 'torrent-moderator')" ;;
        *"super mod"*|*supermod*|*"senior mod"*|*smod*)
            echo "$(find_unit3d_group_by_slug 'moderator')" ;;
        *mod*|*moderator*|*modo*)
            echo "$(find_unit3d_group_by_slug 'moderator')" ;;
        *staff*)
            echo "$(find_unit3d_group_by_slug 'moderator')" ;;
        *encoder*|*internal*)
            echo "$(find_unit3d_group_by_slug 'uploader')" ;;
        *uploader*|*upload*)
            echo "$(find_unit3d_group_by_slug 'uploader')" ;;
        *editor*)
            echo "$(find_unit3d_group_by_slug 'editor')" ;;
        *trustee*|*trusted*)
            echo "$(find_unit3d_group_by_slug 'trustee')" ;;
        *insane*|*insaneuser*)
            echo "$(find_unit3d_group_by_slug 'insaneuser')" ;;
        *extreme*|*extremeuser*)
            echo "$(find_unit3d_group_by_slug 'extremeuser')" ;;
        *"super user"*|*superuser*)
            echo "$(find_unit3d_group_by_slug 'superuser')" ;;
        *"power user"*|*poweruser*|*elite*)
            echo "$(find_unit3d_group_by_slug 'poweruser')" ;;
        *veteran*|*vet*)
            echo "$(find_unit3d_group_by_slug 'veteran')" ;;
        *seeder*|*"top seeder"*)
            echo "$(find_unit3d_group_by_slug 'seeder')" ;;
        *archivist*)
            echo "$(find_unit3d_group_by_slug 'archivist')" ;;
        *vip*|*donator*|*donor*|*supporter*|*premium*)
            echo "$(find_unit3d_group_by_slug 'poweruser')" ;;
        *ban*|*banned*|*suspended*)
            echo "$(find_unit3d_group_by_slug 'banned')" ;;
        *disabled*|*deactivated*|*locked*)
            echo "$(find_unit3d_group_by_slug 'disabled')" ;;
        *pruned*|*deleted*|*removed*)
            echo "$(find_unit3d_group_by_slug 'pruned')" ;;
        *validating*|*pending*|*unvalidated*|*inactive*|*unconfirmed*|*parked*)
            echo "$(find_unit3d_group_by_slug 'validating')" ;;
        *leech*|*leecher*)
            echo "$(find_unit3d_group_by_slug 'leech')" ;;
        *bot*)
            echo "$(find_unit3d_group_by_slug 'bot')" ;;
        *user*|*member*|*registered*|*normal*|*default*|*regular*|*basic*)
            echo "$(find_unit3d_group_by_slug 'user')" ;;
        *)
            echo "" ;;
    esac
}

# Numeric class → UNIT3D slug mapping (TBDev/TSSE convention)
numeric_class_map() {
    local classval="$1"
    case "$classval" in
        0)  echo "$(find_unit3d_group_by_slug 'user')" ;;
        1)  echo "$(find_unit3d_group_by_slug 'poweruser')" ;;
        2)  echo "$(find_unit3d_group_by_slug 'superuser')" ;;
        3)  echo "$(find_unit3d_group_by_slug 'uploader')" ;;
        4)  echo "$(find_unit3d_group_by_slug 'moderator')" ;;
        5)  echo "$(find_unit3d_group_by_slug 'moderator')" ;;
        6)  echo "$(find_unit3d_group_by_slug 'administrator')" ;;
        7)  echo "$(find_unit3d_group_by_slug 'administrator')" ;;
        -1) echo "$(find_unit3d_group_by_slug 'banned')" ;;
        -2) echo "$(find_unit3d_group_by_slug 'disabled')" ;;
        *)  echo "" ;;
    esac
}

find_unit3d_group_by_slug() {
    local slug="$1"
    dst_query "SELECT id FROM \`groups\` WHERE slug='$slug' LIMIT 1" 2>/dev/null | head -1
}

# Get the fallback 'User' group ID
FALLBACK_GROUP_ID=$(find_unit3d_group_by_slug 'user')
if [[ -z "$FALLBACK_GROUP_ID" ]]; then
    FALLBACK_GROUP_ID=$(dst_query "SELECT id FROM \`groups\` ORDER BY id LIMIT 1" 2>/dev/null | head -1)
fi
log "  Fallback group ID: $FALLBACK_GROUP_ID (${UNIT3D_GROUPS[$FALLBACK_GROUP_ID]:-unknown})"

# Build the group map
log ""
log "── Group Mapping ──"

for sgid in $(echo "${!SOURCE_GROUPS[@]}" | tr ' ' '\n' | sort -n); do
    src_name="${SOURCE_GROUPS[$sgid]}"

    # Try auto-mapping by name first
    mapped_id=$(auto_map_group "$src_name")

    # Try numeric class mapping if name match failed
    if [[ -z "$mapped_id" ]]; then
        mapped_id=$(numeric_class_map "$sgid")
    fi

    if [[ -n "$mapped_id" ]]; then
        GROUP_MAP[$sgid]="$mapped_id"
        log "  AUTO: Source #$sgid '$src_name' → UNIT3D #$mapped_id '${UNIT3D_GROUPS[$mapped_id]:-?}'"
    else
        # Interactive prompt — unmapped group
        echo ""
        echo "┌─────────────────────────────────────────────────────────────"
        echo "│  Unmapped source group: #$sgid '$src_name'"
        echo "│"
        echo "│  Available UNIT3D groups:"
        for gid in $(echo "${!UNIT3D_GROUPS[@]}" | tr ' ' '\n' | sort -n); do
            printf "│    %-4s  %s\n" "$gid" "${UNIT3D_GROUPS[$gid]}"
        done
        echo "│"
        echo "│  Enter the UNIT3D group ID to assign these users to."
        echo "│  Press Enter for default (User group #$FALLBACK_GROUP_ID)."
        echo "└─────────────────────────────────────────────────────────────"
        read -rp "  Group ID for '$src_name' [$FALLBACK_GROUP_ID]: " chosen_id

        if [[ -z "$chosen_id" ]]; then
            chosen_id="$FALLBACK_GROUP_ID"
        fi

        # Validate the chosen ID exists
        while [[ -z "${UNIT3D_GROUPS[$chosen_id]+_}" ]]; do
            echo "  Invalid group ID '$chosen_id'. Please choose from the list above."
            read -rp "  Group ID for '$src_name' [$FALLBACK_GROUP_ID]: " chosen_id
            if [[ -z "$chosen_id" ]]; then
                chosen_id="$FALLBACK_GROUP_ID"
            fi
        done

        GROUP_MAP[$sgid]="$chosen_id"
        log "  MANUAL: Source #$sgid '$src_name' → UNIT3D #$chosen_id '${UNIT3D_GROUPS[$chosen_id]}'"
    fi
done

echo ""
log "Group mapping complete: ${#GROUP_MAP[@]} source groups mapped"

# ── Resolve Group for a User ─────────────────────────────────────────────────

resolve_group_id() {
    local src_group_val="$1"

    # Check explicit map first
    if [[ -n "${GROUP_MAP[$src_group_val]+_}" ]]; then
        echo "${GROUP_MAP[$src_group_val]}"
        return
    fi

    # Try numeric class fallback
    local num_mapped
    num_mapped=$(numeric_class_map "$src_group_val")
    if [[ -n "$num_mapped" ]]; then
        echo "$num_mapped"
        return
    fi

    # Ultimate fallback
    echo "$FALLBACK_GROUP_ID"
}

# ── User Migration ───────────────────────────────────────────────────────────

log ""
log "═══════════════════════════════════════════════════════════════"
log "  Starting user migration ($SRC_USER_COUNT source users)"
if [[ $DRY_RUN -eq 1 ]]; then
    log "  *** DRY RUN — no writes ***"
fi
log "═══════════════════════════════════════════════════════════════"

# Disable FK checks on destination
if [[ $DRY_RUN -eq 0 ]]; then
    dst_query "SET FOREIGN_KEY_CHECKS=0"
fi

OFFSET=0
NOW=$(date '+%Y-%m-%d %H:%M:%S')

# Build the SELECT field list dynamically based on available source columns
build_select_fields() {
    local fields="id, username"

    # Email
    if has_src_col "email"; then fields="$fields, email";
    elif has_src_col "email_address"; then fields="$fields, email_address AS email";
    elif has_src_col "mail"; then fields="$fields, mail AS email";
    else fields="$fields, '' AS email"; fi

    # Password bridge
    if has_src_col "passhash"; then fields="$fields, passhash"; else fields="$fields, NULL AS passhash"; fi
    if has_src_col "secret"; then fields="$fields, secret"; else fields="$fields, NULL AS secret"; fi

    # Passkey
    if has_src_col "torrent_pass"; then fields="$fields, torrent_pass";
    elif has_src_col "passkey"; then fields="$fields, passkey AS torrent_pass";
    elif has_src_col "announce_key"; then fields="$fields, announce_key AS torrent_pass";
    else fields="$fields, NULL AS torrent_pass"; fi

    # RSS key
    if has_src_col "rsskey"; then fields="$fields, rsskey";
    elif has_src_col "rss_key"; then fields="$fields, rss_key AS rsskey";
    elif has_src_col "feed_key"; then fields="$fields, feed_key AS rsskey";
    else fields="$fields, NULL AS rsskey"; fi

    # Group
    if [[ -n "$GROUP_COL" ]]; then
        fields="$fields, \`$GROUP_COL\` AS src_group"
    else
        fields="$fields, 0 AS src_group"
    fi

    # Stats
    if has_src_col "uploaded"; then fields="$fields, uploaded"; else fields="$fields, 0 AS uploaded"; fi
    if has_src_col "downloaded"; then fields="$fields, downloaded"; else fields="$fields, 0 AS downloaded"; fi

    if has_src_col "seedbonus"; then fields="$fields, seedbonus";
    elif has_src_col "bonus"; then fields="$fields, bonus AS seedbonus";
    elif has_src_col "points"; then fields="$fields, points AS seedbonus";
    else fields="$fields, 0 AS seedbonus"; fi

    if has_src_col "fl_tokens"; then fields="$fields, fl_tokens";
    elif has_src_col "freeleech_tokens"; then fields="$fields, freeleech_tokens AS fl_tokens";
    else fields="$fields, 0 AS fl_tokens"; fi

    if has_src_col "invites"; then fields="$fields, invites"; else fields="$fields, 0 AS invites"; fi

    if has_src_col "hitandruns"; then fields="$fields, hitandruns";
    elif has_src_col "hnr"; then fields="$fields, hnr AS hitandruns";
    else fields="$fields, 0 AS hitandruns"; fi

    # Profile
    if has_src_col "avatar"; then fields="$fields, avatar AS image";
    elif has_src_col "image"; then fields="$fields, image";
    elif has_src_col "profile_pic"; then fields="$fields, profile_pic AS image";
    else fields="$fields, NULL AS image"; fi

    if has_src_col "title"; then fields="$fields, title";
    elif has_src_col "custom_title"; then fields="$fields, custom_title AS title";
    else fields="$fields, NULL AS title"; fi

    if has_src_col "page"; then fields="$fields, page AS about";
    elif has_src_col "about"; then fields="$fields, about";
    elif has_src_col "profile"; then fields="$fields, profile AS about";
    elif has_src_col "bio"; then fields="$fields, bio AS about";
    else fields="$fields, NULL AS about"; fi

    if has_src_col "signature"; then fields="$fields, signature";
    elif has_src_col "sig"; then fields="$fields, sig AS signature";
    else fields="$fields, NULL AS signature"; fi

    # Donor
    if has_src_col "donor"; then fields="$fields, donor"; else fields="$fields, 'no' AS donor"; fi

    # Permissions
    if has_src_col "can_chat"; then fields="$fields, can_chat"; else fields="$fields, NULL AS can_chat"; fi
    if has_src_col "can_leech"; then fields="$fields, can_leech";
    elif has_src_col "can_download"; then fields="$fields, can_download AS can_leech";
    else fields="$fields, 1 AS can_leech"; fi
    if has_src_col "can_request"; then fields="$fields, can_request"; else fields="$fields, NULL AS can_request"; fi
    if has_src_col "can_invite"; then fields="$fields, can_invite"; else fields="$fields, NULL AS can_invite"; fi
    if has_src_col "can_upload"; then fields="$fields, can_upload"; else fields="$fields, NULL AS can_upload"; fi

    # Status (for email verification)
    if has_src_col "status"; then fields="$fields, status"; else fields="$fields, 'pending' AS status"; fi

    # Timestamps
    if has_src_col "last_login"; then fields="$fields, last_login";
    elif has_src_col "lastvisit"; then fields="$fields, lastvisit AS last_login";
    elif has_src_col "last_seen"; then fields="$fields, last_seen AS last_login";
    else fields="$fields, NULL AS last_login"; fi

    if has_src_col "last_access"; then fields="$fields, last_access";
    elif has_src_col "last_action"; then fields="$fields, last_action AS last_access";
    else fields="$fields, NULL AS last_access"; fi

    if has_src_col "added"; then fields="$fields, added AS created_at";
    elif has_src_col "registered"; then fields="$fields, registered AS created_at";
    elif has_src_col "created_at"; then fields="$fields, created_at";
    elif has_src_col "joindate"; then fields="$fields, joindate AS created_at";
    elif has_src_col "join_date"; then fields="$fields, join_date AS created_at";
    else fields="$fields, NOW() AS created_at"; fi

    echo "$fields"
}

SELECT_FIELDS=$(build_select_fields)
log "  SELECT fields: $SELECT_FIELDS"

# Process users in chunks
while true; do
    log "Processing chunk: OFFSET=$OFFSET, LIMIT=$CHUNK_SIZE"

    # Read the chunk into a temp file (handles large datasets without memory issues)
    TMPFILE=$(mktemp)
    trap "rm -f $TMPFILE" EXIT

    src_query "SELECT $SELECT_FIELDS FROM users ORDER BY id LIMIT $CHUNK_SIZE OFFSET $OFFSET" > "$TMPFILE" 2>>"$LOG_FILE"

    ROW_COUNT=$(wc -l < "$TMPFILE" | tr -d ' ')
    if [[ "$ROW_COUNT" -eq 0 ]]; then
        rm -f "$TMPFILE"
        log "  No more rows — migration complete"
        break
    fi

    log "  Fetched $ROW_COUNT rows"

    BATCH_SQL=""
    SETTINGS_SQL=""
    PRIVACY_SQL=""
    NOTIF_SQL=""
    BATCH_COUNT=0

    while IFS=$'\t' read -r \
        uid username email passhash secret torrent_pass rsskey \
        src_group uploaded downloaded seedbonus fl_tokens invites hitandruns \
        image title about signature donor \
        can_chat can_leech can_request can_invite can_upload \
        status last_login last_access created_at; do

        # Skip empty lines
        [[ -z "$uid" ]] && continue

        # ── Sanitize & transform ──

        # Username: fallback if empty
        if [[ -z "$username" || "$username" == "NULL" ]]; then
            username="user_${uid}"
        fi

        # Email: skip if empty (required field)
        if [[ -z "$email" || "$email" == "NULL" ]]; then
            log "  SKIP user #$uid ($username): no email address"
            ((SKIPPED++)) || true
            continue
        fi

        # Passkey: preserve or generate
        if [[ -z "$torrent_pass" || "$torrent_pass" == "NULL" ]]; then
            torrent_pass=$(random_hex)
        fi

        # RSS key: preserve or generate
        if [[ -z "$rsskey" || "$rsskey" == "NULL" ]]; then
            rsskey=$(random_hex)
        fi

        # Group mapping
        if [[ -z "$src_group" || "$src_group" == "NULL" ]]; then
            src_group=0
        fi
        dest_group_id=$(resolve_group_id "$src_group")

        # Legacy auth bridge
        local_legacy=0
        local_passhash="NULL"
        local_secret="NULL"
        if [[ -n "$passhash" && "$passhash" != "NULL" && -n "$secret" && "$secret" != "NULL" ]]; then
            local_legacy=1
            local_passhash="'$(sql_escape "$passhash")'"
            local_secret="'$(sql_escape "$secret")'"
        fi

        # Donor flag
        is_donor=0
        if [[ "$donor" == "yes" ]]; then
            is_donor=1
        fi

        # Email verification
        email_verified="NULL"
        if [[ "$status" == "confirmed" ]]; then
            email_verified="'$NOW'"
        fi

        # Permissions — handle NULLs
        fmt_perm() {
            local val="$1"
            if [[ -z "$val" || "$val" == "NULL" ]]; then
                echo "NULL"
            elif [[ "$val" == "1" || "$val" == "yes" ]]; then
                echo "1"
            else
                echo "0"
            fi
        }

        p_can_chat=$(fmt_perm "$can_chat")
        p_can_download=$(fmt_perm "$can_leech")
        p_can_request=$(fmt_perm "$can_request")
        p_can_invite=$(fmt_perm "$can_invite")
        p_can_upload=$(fmt_perm "$can_upload")

        # Default can_download to 1 if NULL
        if [[ "$p_can_download" == "NULL" ]]; then
            p_can_download=1
        fi

        # Parse timestamps
        ts_last_login=$(parse_date "$last_login")
        ts_last_action=$(parse_date "$last_access")
        ts_created_at=$(parse_date "$created_at")
        if [[ "$ts_created_at" == "NULL" ]]; then
            ts_created_at="'$NOW'"
        fi

        # Escape string fields
        e_username=$(sql_escape "$username")
        e_email=$(sql_escape "$email")
        e_passkey=$(sql_escape "$torrent_pass")
        e_rsskey=$(sql_escape "$rsskey")
        e_image="NULL"
        e_title="NULL"
        e_about="NULL"
        e_signature="NULL"
        if [[ -n "$image" && "$image" != "NULL" ]]; then e_image="'$(sql_escape "$image")'"; fi
        if [[ -n "$title" && "$title" != "NULL" ]]; then e_title="'$(sql_escape "$title")'"; fi
        if [[ -n "$about" && "$about" != "NULL" ]]; then e_about="'$(sql_escape "$about")'"; fi
        if [[ -n "$signature" && "$signature" != "NULL" ]]; then e_signature="'$(sql_escape "$signature")'"; fi

        # Stats: ensure numeric
        uploaded="${uploaded:-0}"; [[ "$uploaded" == "NULL" ]] && uploaded=0
        downloaded="${downloaded:-0}"; [[ "$downloaded" == "NULL" ]] && downloaded=0
        seedbonus="${seedbonus:-0}"; [[ "$seedbonus" == "NULL" ]] && seedbonus=0
        fl_tokens="${fl_tokens:-0}"; [[ "$fl_tokens" == "NULL" ]] && fl_tokens=0
        invites="${invites:-0}"; [[ "$invites" == "NULL" ]] && invites=0
        hitandruns="${hitandruns:-0}"; [[ "$hitandruns" == "NULL" ]] && hitandruns=0

        # ── Build INSERT ... ON DUPLICATE KEY UPDATE ──
        # Uses id as primary key for matching. If username/email conflict with a DIFFERENT
        # user, MySQL will raise a duplicate key error which we catch and log.
        #
        # Merge mode: COALESCE(VALUES(col), existing_col) — only overwrite if source non-NULL

        BATCH_SQL+="
INSERT INTO users (
    id, username, email, password,
    legacy_passhash, legacy_secret, \`legacy\`,
    passkey, rsskey, group_id,
    uploaded, downloaded, seedbonus, fl_tokens, invites, hitandruns,
    image, title, about, signature,
    is_donor, can_chat, can_download, can_request, can_invite, can_upload,
    email_verified_at, last_login, last_action, created_at, updated_at
) VALUES (
    $uid, '$e_username', '$e_email', '!',
    $local_passhash, $local_secret, $local_legacy,
    '$e_passkey', '$e_rsskey', $dest_group_id,
    $uploaded, $downloaded, $seedbonus, $fl_tokens, $invites, $hitandruns,
    $e_image, $e_title, $e_about, $e_signature,
    $is_donor, $p_can_chat, $p_can_download, $p_can_request, $p_can_invite, $p_can_upload,
    $email_verified, $ts_last_login, $ts_last_action, $ts_created_at, '$NOW'
) ON DUPLICATE KEY UPDATE
    username = COALESCE(VALUES(username), username),
    email = COALESCE(VALUES(email), email),
    password = IF(VALUES(\`legacy\`) = 1, '!', password),
    legacy_passhash = COALESCE(VALUES(legacy_passhash), legacy_passhash),
    legacy_secret = COALESCE(VALUES(legacy_secret), legacy_secret),
    \`legacy\` = IF(VALUES(\`legacy\`) = 1, 1, \`legacy\`),
    passkey = COALESCE(VALUES(passkey), passkey),
    rsskey = COALESCE(VALUES(rsskey), rsskey),
    group_id = VALUES(group_id),
    uploaded = IF(VALUES(uploaded) > 0, VALUES(uploaded), uploaded),
    downloaded = IF(VALUES(downloaded) > 0, VALUES(downloaded), downloaded),
    seedbonus = IF(VALUES(seedbonus) > 0, VALUES(seedbonus), seedbonus),
    fl_tokens = IF(VALUES(fl_tokens) > 0, VALUES(fl_tokens), fl_tokens),
    invites = IF(VALUES(invites) > 0, VALUES(invites), invites),
    hitandruns = IF(VALUES(hitandruns) > 0, VALUES(hitandruns), hitandruns),
    image = COALESCE(VALUES(image), image),
    title = COALESCE(VALUES(title), title),
    about = COALESCE(VALUES(about), about),
    signature = COALESCE(VALUES(signature), signature),
    is_donor = IF(VALUES(is_donor) = 1, 1, is_donor),
    can_chat = COALESCE(VALUES(can_chat), can_chat),
    can_download = COALESCE(VALUES(can_download), can_download),
    can_request = COALESCE(VALUES(can_request), can_request),
    can_invite = COALESCE(VALUES(can_invite), can_invite),
    can_upload = COALESCE(VALUES(can_upload), can_upload),
    email_verified_at = COALESCE(VALUES(email_verified_at), email_verified_at),
    last_login = COALESCE(VALUES(last_login), last_login),
    last_action = COALESCE(VALUES(last_action), last_action),
    updated_at = '$NOW';
"

        # Related records (INSERT IGNORE — won't overwrite existing)
        SETTINGS_SQL+="INSERT IGNORE INTO user_settings (user_id, censor, chat_hidden, locale, style, torrent_layout, torrent_filters, custom_css, standalone_css, show_poster, show_adult_content, created_at, updated_at) VALUES ($uid, 0, 0, 'en', 0, 0, 0, NULL, NULL, 0, 1, '$NOW', '$NOW');
"
        PRIVACY_SQL+="INSERT IGNORE INTO user_privacy (user_id, private_profile, hidden, show_achievement, show_bon, show_comment, show_download, show_follower, show_post, show_profile, show_profile_about, show_profile_achievement, show_profile_badge, show_profile_follower, show_profile_title, show_profile_bon_extra, show_profile_comment_extra, show_profile_forum_extra, show_profile_request_extra, show_profile_torrent_count, show_profile_torrent_extra, show_profile_torrent_ratio, show_profile_torrent_seed, show_profile_warning, show_rank, show_requested, show_topic, show_upload, show_wishlist, json_profile_groups, json_torrent_groups, json_forum_groups, json_bon_groups, json_comment_groups, json_wishlist_groups, json_follower_groups, json_achievement_groups, json_rank_groups, json_request_groups, json_other_groups) VALUES ($uid, 0, 0, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]');
"
        NOTIF_SQL+="INSERT IGNORE INTO user_notifications (user_id, block_notifications, show_bon_gift, show_mention_forum_post, show_mention_article_comment, show_mention_request_comment, show_mention_torrent_comment, show_subscription_topic, show_subscription_forum, show_forum_topic, show_following_upload, show_request_bounty, show_request_comment, show_request_fill, show_request_fill_approve, show_request_fill_reject, show_request_claim, show_request_unclaim, show_torrent_comment, show_torrent_tip, show_torrent_thank, show_account_follow, show_account_unfollow, json_account_groups, json_bon_groups, json_mention_groups, json_request_groups, json_torrent_groups, json_forum_groups, json_following_groups, json_subscription_groups) VALUES ($uid, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]');
"

        ((BATCH_COUNT++)) || true

        if [[ $DRY_RUN -eq 1 ]]; then
            log "  [DRY RUN] Would migrate user #$uid ($username) → group #$dest_group_id (${UNIT3D_GROUPS[$dest_group_id]:-?})"
        fi

        # Flush batch to DB periodically
        if [[ $BATCH_COUNT -ge 50 && $DRY_RUN -eq 0 ]]; then
            if dst_query "$BATCH_SQL" 2>>"$LOG_FILE"; then
                dst_query "$SETTINGS_SQL" 2>>"$LOG_FILE" || true
                dst_query "$PRIVACY_SQL" 2>>"$LOG_FILE" || true
                dst_query "$NOTIF_SQL" 2>>"$LOG_FILE" || true
                ((MIGRATED += BATCH_COUNT)) || true
            else
                log_error "  Batch insert failed at offset $OFFSET"
                ((ERRORS += BATCH_COUNT)) || true
            fi
            BATCH_SQL=""
            SETTINGS_SQL=""
            PRIVACY_SQL=""
            NOTIF_SQL=""
            BATCH_COUNT=0
        fi

    done < "$TMPFILE"

    # Flush remaining batch
    if [[ $BATCH_COUNT -gt 0 ]]; then
        if [[ $DRY_RUN -eq 0 ]]; then
            if dst_query "$BATCH_SQL" 2>>"$LOG_FILE"; then
                dst_query "$SETTINGS_SQL" 2>>"$LOG_FILE" || true
                dst_query "$PRIVACY_SQL" 2>>"$LOG_FILE" || true
                dst_query "$NOTIF_SQL" 2>>"$LOG_FILE" || true
                ((MIGRATED += BATCH_COUNT)) || true
            else
                log_error "  Batch insert failed at offset $OFFSET"
                ((ERRORS += BATCH_COUNT)) || true
            fi
        else
            ((MIGRATED += BATCH_COUNT)) || true
        fi
    fi

    rm -f "$TMPFILE"

    # Check if we've processed all rows
    if [[ $ROW_COUNT -lt $CHUNK_SIZE ]]; then
        break
    fi

    OFFSET=$((OFFSET + CHUNK_SIZE))
done

# Re-enable FK checks
if [[ $DRY_RUN -eq 0 ]]; then
    dst_query "SET FOREIGN_KEY_CHECKS=1"
fi

# ── Summary ──────────────────────────────────────────────────────────────────

echo ""
log "═══════════════════════════════════════════════════════════════"
log "  Migration Complete"
log "═══════════════════════════════════════════════════════════════"
log "  Migrated/Updated: $MIGRATED"
log "  Skipped:          $SKIPPED"
log "  Errors:           $ERRORS"
log "  Log file:         $LOG_FILE"
if [[ $DRY_RUN -eq 1 ]]; then
    log "  *** This was a DRY RUN — no data was written ***"
fi
log "═══════════════════════════════════════════════════════════════"

# Verify related records were created
if [[ $DRY_RUN -eq 0 ]]; then
    SETTINGS_COUNT=$(dst_query "SELECT COUNT(*) FROM user_settings" 2>/dev/null)
    PRIVACY_COUNT=$(dst_query "SELECT COUNT(*) FROM user_privacy" 2>/dev/null)
    NOTIF_COUNT=$(dst_query "SELECT COUNT(*) FROM user_notifications" 2>/dev/null)
    DST_FINAL_COUNT=$(dst_query "SELECT COUNT(*) FROM users" 2>/dev/null)

    log ""
    log "  Post-migration verification:"
    log "    Users in destination:     $DST_FINAL_COUNT"
    log "    user_settings records:    $SETTINGS_COUNT"
    log "    user_privacy records:     $PRIVACY_COUNT"
    log "    user_notifications:       $NOTIF_COUNT"
fi

exit 0
