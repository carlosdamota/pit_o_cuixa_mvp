#!/usr/bin/env bash
# Pit o Cuixa — Cron Menu Sync (Linux)
# For dinahosting "Tareas programadas" scheduler.
# Reads SERVICE_API_TOKEN from .env and POSTs to /api/update-menu.
#
# Scheduler command: bash /home/USUARIO/pit_o_cuixa_mvp/scripts/cron-sync-linux.sh

set -eu

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/../.env"

if [ ! -f "$ENV_FILE" ]; then
    echo "[cron-sync] ERROR: .env not found at $ENV_FILE"
    exit 1
fi

TOKEN=$(grep -E '^SERVICE_API_TOKEN\s*=' "$ENV_FILE" | head -1 | sed 's/^[^=]*=\s*//')
SITE_URL=$(grep -E '^SITE_URL\s*=' "$ENV_FILE" | head -1 | sed 's/^[^=]*=\s*//')

if [ -z "$TOKEN" ]; then
    echo "[cron-sync] ERROR: SERVICE_API_TOKEN empty in .env"
    exit 1
fi

SITE_URL="${SITE_URL:-https://pitocuixa.es}"
URL="${SITE_URL}/api/update-menu"

HTTP_CODE=$(curl -s -o /tmp/cron-sync-response.txt -w "%{http_code}" \
    -X POST \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    --max-time 120 \
    "$URL")

BODY=$(cat /tmp/cron-sync-response.txt 2>/dev/null || echo "")

if [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
    echo "[cron-sync] OK: HTTP $HTTP_CODE"
    exit 0
else
    echo "[cron-sync] FAILED: HTTP $HTTP_CODE — $BODY"
    exit 1
fi
