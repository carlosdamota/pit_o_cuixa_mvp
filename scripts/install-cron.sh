#!/usr/bin/env bash
#
# Pit o Cuixa — Install Cron Menu Sync Job
#
# Installs a cron entry that refreshes the menu TWICE daily (00:00 and 12:00
# server local time) via the session-free service-credential sync
# (scripts/cron-sync.php), appending output to data/cron-sync.log.
#
# Idempotent: if a cron-sync.php entry already exists in the crontab, nothing
# is changed. As a migration step, any legacy fill-menu.php entry (the old
# nightly 02:00 job) is removed/retired from the crontab.
#
# Usage:
#   ./scripts/install-cron.sh
#
# Note: uses the absolute path to the `php` binary found via PATH, so the
# job works even though cron runs with a minimal environment.
#
set -u

# Resolve the project root from this script's location (handles symlinks).
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

PHP_BIN="$(command -v php || true)"
if [ -z "$PHP_BIN" ]; then
    echo "[install-cron] ERROR: 'php' not found in PATH." >&2
    exit 1
fi

# crontab is required to install (and inspect) the job.
if ! command -v crontab >/dev/null 2>&1; then
    echo "[install-cron] ERROR: 'crontab' not found in PATH." >&2
    exit 1
fi

# The cron job appends to data/cron-sync.log — make sure data/ exists
# before cron tries to redirect into it.
mkdir -p "$PROJECT_DIR/data"

CRON_LINE="0 0,12 * * * ${PHP_BIN} ${PROJECT_DIR}/scripts/cron-sync.php >> ${PROJECT_DIR}/data/cron-sync.log 2>&1"

# NOTE: the log grows indefinitely — rotate it (e.g. logrotate) or truncate
# periodically with: > "$PROJECT_DIR/data/cron-sync.log"

# Idempotency check: -F matches the exact script path as a fixed string
# (no regex interpretation of '.' in cron-sync.php). If the new job is already
# installed, nothing to do.
if crontab -l 2>/dev/null | grep -F -q 'cron-sync.php'; then
    echo "[install-cron] A cron-sync.php cron job is already installed — nothing to do."
    echo "[install-cron] Current entries:"
    crontab -l 2>/dev/null | grep -F 'cron-sync.php'
    exit 0
fi

# Migrate: drop any legacy fill-menu.php job (old nightly 02:00 line) so the
# retired fill-menu cron path is not left running alongside the new sync.
CURRENT="$(crontab -l 2>/dev/null || true)"
if printf '%s\n' "$CURRENT" | grep -F -q 'fill-menu.php'; then
    echo "[install-cron] Removing legacy fill-menu.php cron job (retired)."
    CURRENT="$(printf '%s\n' "$CURRENT" | grep -F -v 'fill-menu.php' || true)"
fi

# Install: keep all other entries, append the new sync job.
{ printf '%s\n' "$CURRENT"; [ -n "$CURRENT" ] && printf '\n'; printf '%s\n' "$CRON_LINE"; } | sed '/^[[:space:]]*$/d' | crontab -

echo "[install-cron] Installed twice-daily menu sync (00:00 and 12:00)."
echo "[install-cron] Line added:"
echo "  $CRON_LINE"
echo "[install-cron] Log file: ${PROJECT_DIR}/data/cron-sync.log"
