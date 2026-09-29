#!/usr/bin/env bash
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKSPACE="$(cd "$SCRIPT_DIR/../.." && pwd)"
LESSONS="$WORKSPACE/.claude/tasks/lessons.md"

if [ -s "$LESSONS" ]; then
  echo "=== Lessons from prior sessions (read these before acting) ==="
  cat "$LESSONS"
  echo "=== End of lessons ==="
else
  echo "[lessons] No lessons recorded yet. Use /lesson to add one."
fi

exit 0