---
name: bake
description: >
  Guardrailed wrapper around `bin/cake bake`. Runs the bake command, then
  post-processes generated files: strip docblock comments, confirm
  declare(strict_types=1), run php-cs-fixer, run /quality --changed.
---

# Bake

`bin/cake bake` is convenient but generates code that doesn't follow our conventions out of the box. This skill wraps it.

## Arguments

`$ARGUMENTS` is passed through to `bin/cake bake`:

- `/bake model Users`
- `/bake controller Posts --no-actions`
- `/bake migration CreateUsersTable`

## Workflow

### Step 1 — Pre-flight

Snapshot the list of `.php` files in `src/`, `tests/`, and `config/` so we can identify what bake generated:

```bash
find src tests config -name '*.php' > /tmp/bake-pre.txt
```

### Step 2 — Run bake

```bash
bin/cake bake $ARGUMENTS --no-interaction 2>&1 | tee /tmp/bake-output.txt
```

If `--no-interaction` fails (some bake subcommands require prompts), retry without it and pass control to the user via the conversation.

### Step 3 — Identify generated files

```bash
find src tests config -name '*.php' > /tmp/bake-post.txt
NEW_FILES=$(comm -13 <(sort /tmp/bake-pre.txt) <(sort /tmp/bake-post.txt))
```

`NEW_FILES` is the list of files bake created.

### Step 4 — Post-process each generated file

For each file in `NEW_FILES`:

1. **Strip generated docblock comments** at the top (e.g. `/** @link https://book.cakephp.org/5/...` blocks). Use a sed pattern or just open and edit. Per our rules, generated comments are noise.
2. **Confirm `declare(strict_types=1);`** is present on line 2 (after `<?php`). If missing, insert it.
3. **Remove unused method stubs** if bake generated empty actions or fields you didn't request. Leave only what was asked for.

### Step 5 — Format and validate

```bash
vendor/bin/php-cs-fixer fix -- $NEW_FILES
```

Then run the quality gate scoped to changes:

```bash
/quality --changed
```

If quality fails on a generated file, surface the failures and ask the user how to address. Do NOT silently fix issues that bake created — they're signal that bake's defaults disagree with our rules, and that may be worth a `/lesson` entry.

### Step 6 — Report

Output to the user:
- Files generated (with paths)
- Files post-processed (with what was stripped/added)
- Quality gate status

## Composition

- This skill never decides WHAT to bake — that comes from the user's args.
- This skill enforces post-bake hygiene; without it, every bake reintroduces forbidden patterns.
