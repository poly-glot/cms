---
name: quality
description: >
  Run the full local quality gate against the workspace: cs-fixer (format),
  PHPStan (static analysis at level max), Rector (modernization check), PHPUnit
  (tests). Use before pushing or merging.
---

# Quality Gate

Local CI: runs the same checks CI would. No agent — direct composer invocation.

## Arguments

Parse `$ARGUMENTS`:

- **No args** → run the full gate against `src/` and `tests/`.
- **`--fix`** → after dry-run review, apply Rector changes, then re-run gate.
- **`--coverage`** → run PHPUnit with `--coverage-text`.
- **`--changed`** → scope to files changed vs `origin/main`.

## Workflow

### Step 1 — Gate sequence

```bash
composer cs-check    # PHP-CS-Fixer dry-run
composer phpstan     # PHPStan analyse at level max
composer rector-dry  # Rector dry-run (suggestions only)
composer test        # PHPUnit
npm run lint         # Prettier check, ESLint, Stylelint over webroot/js + webroot/css
```

If any step fails, STOP and report the failing step's output. Do not proceed to later steps.

### Step 2 — `--changed` scope

When `--changed` is in `$ARGUMENTS`:

```bash
CHANGED=$(git diff --name-only origin/main...HEAD -- '*.php')
[ -z "$CHANGED" ] && { echo "No PHP changes vs origin/main."; exit 0; }

vendor/bin/php-cs-fixer fix --dry-run --diff -- $CHANGED
vendor/bin/phpstan analyse --no-progress -- $CHANGED
vendor/bin/rector process --dry-run -- $CHANGED
vendor/bin/phpunit --filter '<inferred from changed test files>' || vendor/bin/phpunit
```

For test selection: if changed files include `tests/TestCase/...`, run those specifically. If they include `src/...`, run the matching `tests/TestCase/...` files. If unclear, run the full suite.

If `git diff --name-only origin/main...HEAD -- webroot/js webroot/css` is non-empty, also run `npm run lint`.

### Step 3 — `--fix` mode

```bash
composer rector-dry    # Show what would change
# Wait for user confirmation
composer rector        # Apply
composer cs-fix        # Format the result
composer phpstan       # Confirm still clean
composer test          # Confirm tests pass
```

### Step 4 — `--coverage` mode

```bash
composer test-coverage
```

Surface the coverage percentage and any files below 80%.

## Output

- On full pass: `✓ Quality gate passed (cs-check · phpstan · rector-dry · test · npm lint).`
- On failure: print the failing step's output verbatim, plus a one-line summary `✗ Quality gate failed at <step>.`
- Never silently swallow output.

## Rules

- Exit non-zero on any gate failure.
- Do not auto-apply Rector fixes without `--fix` in args.
- Do not regenerate the PHPStan baseline as a "fix" for new errors — that hides real problems.
