---
name: refactor-safely
description: >
  Guarded refactor loop using Rector. Snapshots test result, runs Rector
  dry-run, presents diff for review, applies on confirmation, re-runs tests,
  auto-reverts if tests go red.
---

# Refactor Safely

Run Rector with a safety net. Tests must be green before and after.

## Arguments

- **No args** → refactor whole `src/`.
- **`<path>`** → scope to a directory (e.g. `/refactor-safely src/Service`).

## Pre-flight checks

### 1. Working tree must be clean

```bash
git status --porcelain
```

If non-empty, STOP. Tell the user: "Working tree has uncommitted changes. Commit or stash first — the safety net needs a clean baseline to revert to."

Do NOT offer to stash automatically. The user must take that action explicitly.

### 2. Tests must be green NOW

```bash
composer test
```

If red, STOP. Tell the user: "Tests are red before refactor. Fix the failing tests first — refactoring on red is unsafe."

## Workflow

### Step 1 — Dry-run

```bash
vendor/bin/rector process --dry-run <path-or-empty>
```

Capture the suggested changes. If empty ("nothing to change"), report and exit.

### Step 2 — Review

Present the dry-run output to the user. Highlight any structural changes (renames, removed parameters, changed signatures) that could break callers. Ask: "Apply these changes?"

If the user declines, exit with the dry-run report saved to a comment in the conversation. Do not apply.

### Step 3 — Apply

```bash
vendor/bin/rector process <path-or-empty>
composer cs-fix
```

### Step 4 — Verify

```bash
composer phpstan
composer test
```

### Step 5 — On red, revert

If either Step 4 command fails:

```bash
git restore .
```

Report the failing test or PHPStan error verbatim. Do NOT commit. Tell the user: "Refactor reverted — `<command>` failed with `<error>`. The proposed changes broke something; investigate the failing case before re-attempting."

### Step 6 — On green, present for commit

Do NOT auto-commit. Show the diff via `git diff --stat`. Suggest:

```bash
/commit  # to stage and commit with a generated message
```

Let the user run `/commit` themselves so they can review.

## Why the safety dance

- Rector is high-leverage but can change semantics in edge cases (e.g. type-narrowing rules that drop a `null` check).
- The dance — clean tree → green tests → dry-run → apply → re-verify → revert on red — makes each step reversible without losing work.
- Auto-revert is OK because we required a clean tree at Step 0. Nothing manually-edited gets lost.
