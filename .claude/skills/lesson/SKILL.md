---
name: lesson
description: >
  Append a self-correction entry to .claude/tasks/lessons.md. Use after the
  user corrects an approach or pattern. Suggests promotion to a topic rule
  file if the lesson generalises.
---

# Lesson

Append to `.claude/tasks/lessons.md` after a user correction.

## Arguments

- **No args** → ask the user three things in one message:
  - What just happened that should not happen again?
  - What should you do instead?
  - Why does this matter (past mistake, constraint, convention)?
- **`"<rule>"`** → append direct with that rule as the headline, ask for Trigger/Do/Why/See inline.

## Workflow

### Step 1 — Gather entry fields

Required:
- **Headline:** one-line rule, present-tense imperative.
- **Trigger:** when does this apply?
- **Do:** what to do.
- **Why:** reason or past mistake.

Optional:
- **See:** link to a related `.claude/rules/<topic>.md` if applicable.

### Step 2 — Append to lessons.md

Format (append below the existing entries, before any closing content):

```markdown

### YYYY-MM-DD — <headline>
**Trigger:** <when>
**Do:** <what>
**Why:** <reason>
**See:** <optional path>
```

Use today's date. Don't replace existing entries — append.

### Step 3 — Promotion suggestion

After appending, scan the lesson:

- Does it apply to a specific topic already covered by a rule file (`php.md`, `cakephp.md`, etc.)?

If yes, suggest: "This lesson aligns with `.claude/rules/<topic>.md`. Consider promoting it: edit the rule file to incorporate the guidance, then remove the entry from `lessons.md` to keep the lessons file focused on recent corrections."

Don't auto-promote. The user decides whether the lesson is general enough.

### Step 4 — Commit

Suggest committing the lesson:

```
/commit
```

The commit message will be auto-generated as `docs(claude): add lesson on <headline>` per the commit skill's scope-inference (single colon-space, no em-dash — conventional-commit linter compatibility).

## Rules

- Always use today's date (system date).
- One lesson per `/lesson` invocation. If the user has multiple unrelated corrections, prompt them to file each as a separate lesson.
- Don't paraphrase the user's words into something more generic — keep the specific wording that captures what made them correct you.
