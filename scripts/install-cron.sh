#!/usr/bin/env bash
#
# Pit o Cuixa — Install Nightly Menu Fill Cron Job
#
# Installs a cron entry that re-fills the database from the external menu
# every day at 02:00, appending output to data/fill-menu-cron.log.
#
# Idempotent: if a fill-menu.php entry already exists in the crontab,
# nothing is changed.
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

# The cron job appends to data/fill-menu-cron.log — make sure data/ exists
# before cron tries to redirect into it.
mkdir -p "$PROJECT_DIR/data"

CRON_LINE="0 2 * * * ${PHP_BIN} ${PROJECT_DIR}/scripts/fill-menu.php >> ${PROJECT_DIR}/data/fill-menu-cron.log 2>&1"

# NOTE: the log grows indefinitely — rotate it (e.g. logrotate) or truncate
# periodically with: > "$PROJECT_DIR/data/fill-menu-cron.log"

# Idempotency check: -F matches the exact script path as a fixed string
# (no regex interpretation of '.' in fill-menu.php).
if crontab -l 2>/dev/null | grep -F -q 'fill-menu.php'; then
    echo "[install-cron] A fill-menu.php cron job is already installed — nothing to do."
    echo "[install-cron] Current entries:"
    crontab -l 2>/dev/null | grep -F 'fill-menu.php'
    exit 0
fi

( crontab -l 2>/dev/null; echo "$CRON_LINE" ) | crontab -

echo "[install-cron] Installed nightly menu fill (02:00)."
echo "[install-cron] Line added:"
echo "  $CRON_LINE"
echo "[install-cron] Log file: ${PROJECT_DIR}/data/fill-menu-cron.log"
