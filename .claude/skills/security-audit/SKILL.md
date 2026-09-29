---
name: security-audit
description: >
  Security pass. With no args, audits the current branch's diff. With "full",
  audits the whole src/ tree. With a path, scopes to that directory. Always
  runs composer audit first and feeds the result into the agent prompt.
---

# Security Audit

Dispatcher for `security-auditor` agent.

## Arguments

- **No args** → audit current branch's diff vs `origin/main`.
- **`full`** → audit whole `src/` tree (warn user; this is slower).
- **`<path>`** → audit a directory (e.g. `/security-audit src/Controller`).

## Workflow

### Step 1 — Pre-flight composer audit

```bash
composer audit --format=plain 2>&1 | tee /tmp/composer-audit.txt
```

Capture output. Pass to agent in the prompt.

### Step 2 — Determine review subject

| Args | Subject |
|---|---|
| (none) | Diff vs `origin/main` — agent runs in worktree |
| `full` | Whole `src/` and `config/` tree — agent reads in place (no worktree needed) |
| `<path>` | The specified directory — agent reads in place |

### Step 3 — Spawn security-auditor

```
Agent({
  subagent_type: "security-auditor",
  isolation: <worktree if diff mode, else null>,
  description: "Security audit — <scope>",
  prompt: <see below>
})
```

Prompt must include:
- **Audit scope** — diff / full / path.
- **`composer audit` output** from Step 1.
- **Worktree setup commands** (diff mode only).
- **Reminder to apply CakePHP-specific lens** (the agent already knows this from its own prompt).

### Step 4 — Relay report

The agent's output is the source of truth. Lead with severity counts, then findings.

If the agent surfaces any **Critical** findings, add a banner above the report: `⚠ CRITICAL FINDINGS — review before any deploy.`
