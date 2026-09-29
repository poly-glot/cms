# CakePHP CMS — Claude Working Agreement

You are working on a CakePHP 5 / PHP 8.4 / MySQL 8 codebase. This file is the index; the detail lives in `.claude/rules/`. Read the relevant rule file before touching that area.

## Default model

Use Opus 4.7 (`claude-opus-4-7`). Don't downgrade to save tokens — correctness over economy.

## Core principles (apply to every change)

1. **Simplicity first.** Make every change as simple as possible. Touch minimum code. Three similar lines beats a premature abstraction.
2. **No laziness.** Find the root cause. No temporary fixes, no patch-overs, no "TODO: clean up later." Senior-developer standards.
3. **Minimal impact.** Changes touch only what's necessary. Don't refactor adjacent code unless it's blocking the task.
4. **Demand elegance — balanced.** For non-trivial changes (>~30 lines, new abstraction, multi-layer), pause and invoke `elegance-challenger` before presenting. For obvious one-line fixes, skip it. Challenge your own work before you ship it. If a fix feels hacky, ask yourself: "knowing what I know now, what's the elegant version?"

## Subagent strategy

Use subagents liberally. Throw compute at the problem; don't ration tokens.

- Heavy reading (whole-diff review, security sweep, coverage analysis) → spawn the matching agent. Keeps the main context clean.
- Independent investigations in parallel → multiple subagents in one message.
- One concern per subagent — don't ask code-reviewer to also write tests.

The four agents live in `.claude/agents/`: `code-reviewer`, `test-engineer`, `security-auditor`, `elegance-challenger`. All run Opus.

## Self-improvement loop

After ANY correction from the user, append to `.claude/tasks/lessons.md` using `/lesson` (or the format below). Then ruthlessly iterate: re-read at session start, apply the rule, drop the mistake rate.

The `SessionStart` hook prints `lessons.md` into context on turn 1 — you don't need to re-read it manually mid-session unless something feels familiar.

Lesson entry format:

```
### YYYY-MM-DD — <one-line rule>
**Trigger:** <when this applies>
**Do:** <what to do>
**Why:** <reason / past mistake>
**See:** <optional link to rule file>
```

If a lesson generalises, promote it to `.claude/rules/<topic>.md` and remove the duplicate.

## Rules (read on demand)

| File | When to read |
|---|---|
| `.claude/rules/common.md` | Any text-file edit (EOF newlines, encoding) |
| `.claude/rules/php.md` | Any `.php` edit (PHP 8.4 idioms, readonly, enums, property hooks) |
| `.claude/rules/style.md` | Naming, declarative/functional preferences |
| `.claude/rules/cakephp.md` | Anything under `src/Controller`, `src/Model`, `src/Service`, `templates/` |
| `.claude/rules/testing.md` | Any `tests/` edit |
| `.claude/rules/error-handling.md` | New exception types, catch blocks, error responses |
| `.claude/rules/security.md` | Auth, validation, queries, file upload, output rendering |
| `.claude/rules/architecture.md` | New file/folder, deciding where a class belongs |
| `.claude/rules/frontend.md` | Any `webroot/` JS/CSS edit (the kit, data-attribute registry, mounting conventions) |

## Skills (slash commands)

| Command | Purpose |
|---|---|
| `/quality` | Run the full local gate (cs-fix → phpstan → rector dry → phpunit → npm lint) |
| `/test [<class> \| bug "<desc>"]` | Coverage analysis or Prove-It test for a bug |
| `/review [<ref>]` | Dispatch code-reviewer agent in a worktree |
| `/security-audit [<path>]` | Dispatch security-auditor + `composer audit` |
| `/refactor-safely [<path>]` | Rector dry → review → apply → cs-fix → tests (auto-revert on red) |
| `/bake <subcommand>` | Wrapped `bin/cake bake` with post-process cleanup |
| `/commit [squash] [push]` | Conventional commit; never `--no-verify`; pushes only on explicit ask |
| `/debug-issue` | Structured RCA; no fixes proposed until repro + hypothesis confirmed |
| `/explore-codebase` | CakePHP-aware orientation through routes → controllers → tables |
| `/lesson [<rule>]` | Append a self-correction entry to `.claude/tasks/lessons.md` |

## CakePHP quick reference

- Routes: `config/routes.php`
- Controllers: `src/Controller/` — thin, delegate to Service or Table
- Tables / queries / validation: `src/Model/Table/`
- Entities / derived state: `src/Model/Entity/`
- Cross-table orchestration: `src/Service/`
- Tests mirror src: `tests/TestCase/...`
- Fixtures: `tests/Fixture/`
- Authoritative docs: https://book.cakephp.org/5.x/

## What NOT to do (cross-cutting)

- Never `git push` without explicit user permission for that specific push.
- Never `git add -A` or `git add .` — stage by name.
- Never `--no-verify` on commits.
- Never raw SQL when the Query Builder works.
- Never `$_accessible['*' => true]` on Entities.
- Never `catch (\Exception $e)` without re-throw or logged context.
- Never write code comments that restate what the code does — names should carry the meaning.
- Never add an admin AJAX endpoint (autosave, tag attach/detach, drag-reorder, autocomplete, pickers, per-row moderation) without also adding its `[controller, action]` to `RateLimitMiddleware::GENEROUS`. The default tier is a tight 1 req/s (burst 10); bursty XHR left in it gets 429'd and the client drops it silently. Public content + those AJAX endpoints live in the generous (500 rps) tier; auth/public-form POSTs (login, signup, reset, public `Comments::add`) stay strict.

## Reading order on session start

1. This file (auto-loaded).
2. `.claude/tasks/lessons.md` (auto-printed by `SessionStart` hook).
3. The rule file matching the area you're about to touch.

## First-time setup

A new contributor clones the repo, runs the devcontainer (which scaffolds CakePHP + installs deps via `composer install`), then:

```bash
cp .claude/settings.local.json.example .claude/settings.local.json
```

This seeds the recommended per-machine permission allowlist so Claude isn't prompted for every `composer`, `git status`, `vendor/bin/*` invocation. The file is gitignored — edit freely for personal preferences. Re-copy from the example to reset.
