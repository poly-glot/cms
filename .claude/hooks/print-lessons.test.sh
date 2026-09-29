#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HOOK="$SCRIPT_DIR/print-lessons.sh"
WORKSPACE="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$WORKSPACE"

# Test 1: non-empty lessons file prints banner + content
OUTPUT=$("$HOOK" 2>&1)
echo "$OUTPUT" | grep -q "=== Lessons from prior sessions" || { echo "FAIL: missing banner"; exit 1; }
echo "$OUTPUT" | grep -q "Seed entry" || { echo "FAIL: missing lessons content"; exit 1; }

# Test 2: missing lessons file prints fallback
# Build a fake workspace with the hook at <fake>/.claude/hooks/ but no lessons file
TMPDIR=$(mktemp -d)
trap "rm -rf $TMPDIR" EXIT
mkdir -p "$TMPDIR/fakeworkspace/.claude/hooks"
mkdir -p "$TMPDIR/fakeworkspace/.claude/tasks"
cp "$HOOK" "$TMPDIR/fakeworkspace/.claude/hooks/print-lessons.sh"
chmod +x "$TMPDIR/fakeworkspace/.claude/hooks/print-lessons.sh"
# lessons.md intentionally absent — hook should print fallback
OUTPUT=$("$TMPDIR/fakeworkspace/.claude/hooks/print-lessons.sh" 2>&1)
echo "$OUTPUT" | grep -q "No lessons recorded yet" || { echo "FAIL: missing fallback message"; exit 1; }

echo "PASS: print-lessons.sh smoke test"