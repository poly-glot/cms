#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKSPACE="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="/tmp"

cd "$WORKSPACE"

echo "[post-start] Stopping any previous CakePHP dev server..."
pkill -f "bin/cake server" 2>/dev/null || true
pkill -f "php -S 0.0.0.0:8765" 2>/dev/null || true

# Bail out cleanly if scaffold didn't finish or composer.json is missing.
if [ ! -x "$WORKSPACE/bin/cake" ]; then
  echo "[post-start] bin/cake not found — skipping dev server start."
  echo "[post-start] Run post-create.sh again or scaffold the app manually."
  exit 0
fi

echo "[post-start] Starting CakePHP dev server on 0.0.0.0:8765 -> $LOG_DIR/cake.log"
setsid nohup "$WORKSPACE/bin/cake" server -H 0.0.0.0 -p 8765 </dev/null >"$LOG_DIR/cake.log" 2>&1 &
disown || true

sleep 1

echo "[post-start] Done. App: http://localhost:8765  |  Tail logs: tail -f /tmp/cake.log"
