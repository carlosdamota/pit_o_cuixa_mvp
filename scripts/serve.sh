#!/usr/bin/env bash
#
# Pit o Cuixa — Dev Server Launcher
#
# Fills the database from the external menu (php scripts/fill-menu.php) and
# THEN starts the PHP built-in development server. This guarantees a fresh
# checkout with an empty catalog gets populated as soon as the server boots.
#
# If the fill step fails (e.g. no network), a warning is printed and the
# server still starts so the app remains inspectable — run
# `php scripts/fill-menu.php` manually to retry the fill.
#
# Usage:
#   ./scripts/serve.sh            # fill + serve on 0.0.0.0:8000
#   PORT=8080 ./scripts/serve.sh  # optional port override
#
set -u

# Resolve the project root from this script's location (handles symlinks).
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${PORT:-8000}"

cd "$PROJECT_DIR"

echo "[serve] Filling menu database..."
if php scripts/fill-menu.php; then
    echo "[serve] Menu fill OK."
else
    echo "[serve] WARNING: menu fill failed (see output above). Starting server anyway."
fi

echo "[serve] Starting PHP dev server on 0.0.0.0:${PORT} (docroot: public/)..."
exec php -S "0.0.0.0:${PORT}" -t public
