---
name: commit
description: >
  Conventional commit workflow. Parses changes, stages by name (never -A),
  generates a single-line conventional-commit message. Optional squash and
  push actions. Never --no-verify; pushes require explicit per-push consent.
---

# Commit

Composable git workflow. Parse `$ARGUMENTS` for: `squash`, `push`. Default: commit only.

## Actions

### commit (default, runs first when there are unstaged changes)

1. **Gather state** (parallel):
   - `git status` (never `-uall`)
   - `git diff` (staged + unstaged)
   - `git log --oneline -10` (message style reference)

2. **Identify scope** — from touched files:
   - `src/Controller/*` → `Controller`
   - `src/Model/Table/*` → `Model/Table`
   - `src/Service/*` → `Service`
   - `src/Policy/*` → `Policy`
   - `tests/*` → `tests`
   - `.claude/*` → `claude`
   - `config/*` → `config`
   - Multiple → most specific shared prefix, or `multi`

3. **Stage files** — add by name. Never `git add -A` or `git add .`.

   **Skip** files when they're local-only:
   - `config/app_local.php` (gitignored, but warn if user staged it)
   - `.env*` files

4. **Analyze changes** — determine:
   - **type**: `feat` | `fix` | `refactor` | `chore` | `docs` | `test` | `style` | `ci` | `perf` | `build`
   - **scope**: from Step 2
   - **description**: concise, what + why

5. **Commit** — single-line message:

   ```
   git commit -m "<type>(<scope>): <description>"
   ```

   Example: `feat(Service): add registration retry on transient failure`.

   No body. No `Co-Authored-By` unless the user asks. Single line.

6. **Verify** — `git log --oneline -1`.

### squash (when `$ARGUMENTS` contains "squash")

1. Count commits since `origin/main`:

   ```bash
   git log --oneline origin/main..HEAD | wc -l
   ```

2. If 0 or 1, skip squash — nothing to collapse.

3. Read all commit messages on the branch. Synthesize a unified single-line message that captures the full scope.

4. Squash:

   ```bash
   git reset --soft HEAD~N
   git commit -m "<type>(<scope>): <unified description>"
   ```

   Never `git reset --soft origin/main` — that pulls in unrelated upstream changes.

5. Verify — `git log --oneline -1`.

### push (when `$ARGUMENTS` contains "push")

**REQUIRES EXPLICIT CONFIRMATION.** Per the user's global git push policy, every push requires fresh per-push permission.

1. Ask: `Push to origin/<branch>? (force-with-lease)` — wait for explicit yes.

2. Confirm there are commits ahead of remote:

   ```bash
   git log --oneline @{push}..HEAD 2>/dev/null || git log --oneline -1
   ```

3. Push:

   ```bash
   git push --force-with-lease
   ```

4. Verify — `git log --oneline -1` and confirm the remote ref moved.

## Rules

- Never `git add -A` or `git add .` — stage by name.
- Single-line commit messages only — no body, no `Co-Authored-By` unless asked.
- Always `--force-with-lease`, never `--force`.
- Squash synthesizes a new message from all branch commits — not just the first.
- If `squash push` given, squash first, then push.
- Never `--no-verify` (no skipping hooks) unless the user explicitly asks.
- Pushes to `main` or `master` always re-confirm with the user, even if `push` is in args.
