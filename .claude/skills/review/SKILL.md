---
name: review
description: >
  Senior principal code review. With no args, reviews the current branch.
  With a git ref, reviews that ref vs origin/main. Always dispatches to the
  code-reviewer agent running in an isolated worktree. Spawns security-auditor
  in parallel for security-touching diffs.
---

# Code Review — orchestrator

You are running `/review`. This skill is a **thin dispatcher**: figure out WHAT to review, then hand off to the `code-reviewer` agent in an isolated worktree.

## Arguments

Parse `$ARGUMENTS`:

- **No args** → local mode (review current branch vs `origin/main`).
- **`<git-ref>`** (e.g. `origin/feature/foo`) → review that ref vs `origin/main`.

## Step 1 — Resolve subject

### Local mode (no args)

1. `git rev-parse --abbrev-ref HEAD` → branch name.
2. Worktree setup command: `git checkout --detach <branch>` (the agent runs this inside its worktree).

### Ref mode

1. `git fetch origin <ref>`.
2. Worktree setup command: `git checkout origin/<ref>` (auto-detaches).

## Step 2 — Detect security-touching diff

Run:

```bash
git diff --name-only origin/main...HEAD | grep -E '(Controller/|Service/|Policy/|Middleware/|Entity/.*\.php$|_accessible|Authentication|Authorization|webroot/|config/app)' && echo "SECURITY"
```

If `SECURITY`, plan to dispatch `security-auditor` in parallel with `code-reviewer` in Step 3.

## Step 3 — Spawn agents

Always spawn `code-reviewer` with `isolation: worktree`.

If Step 2 flagged security: spawn `security-auditor` in parallel (same message, multiple Agent calls).

The `code-reviewer` prompt must include:

- **Review subject** — git ref or branch name.
- **Worktree setup commands** from Step 1.
- **Reminder of rules location:** `.claude/rules/*` (the agent already knows to sweep them).

The `security-auditor` prompt (when applicable):

- **Review subject** — same as code-reviewer.
- **Worktree setup commands** — same.
- **`composer audit` reminder:** run before reading source.

## Step 4 — Relay reports

The agents' outputs are the source of truth. Lead with the verdict line(s), then findings grouped by severity. Don't add findings from this session. If both agents ran, present them as two distinct sections under headings `## Code Review` and `## Security Audit`.

If `code-reviewer` flagged "simplification opportunities" or many Rector dry-run suggestions, suggest: "Consider running `/refactor-safely` to address the modernization findings."

## Why a skill AND an agent

- **Skill = trigger.** Slash commands are skills.
- **Agent = isolated worker.** Reading a 50-file diff plus the rules sweep dumps tens of thousands of tokens into the session. The agent keeps that out of the main context and enforces a read-only tool allowlist.
- **The boundary:** skill does cheap orchestration (parse args, prep worktree commands, detect security). Agent does the expensive work (read everything, apply criteria, produce report).
