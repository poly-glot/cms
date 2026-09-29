---
name: debug-issue
description: >
  Structured root-cause analysis. Refuses to suggest fixes until reproduction
  steps, expected vs actual, and failing test or stack trace are known. Writes
  a Prove-It test before proposing the fix. Routes non-trivial fixes through
  elegance-challenger.
---

# Debug Issue

Structured RCA. No patch-overs. Find the root cause.

## Required information (gather before proposing anything)

You must have all four before suggesting a fix:

1. **Reproduction steps** — exactly what triggers the bug.
2. **Expected behavior** — what should happen.
3. **Actual behavior** — what does happen.
4. **Failing test or stack trace or log excerpt** — evidence the bug exists.

If any are missing, ask the user — one at a time, not all four at once. Do NOT guess or fabricate.

## Workflow

### Step 1 — Confirm reproduction

Run the reproduction steps yourself (or write a test that runs them). Confirm the bug reproduces. If it doesn't, the bug may already be fixed, the environment may differ, or the description may be incomplete. Report back to the user.

### Step 2 — Form hypothesis

State the hypothesis as a single sentence: "The bug is X, caused by Y in `<file>:<line>`."

Keep it falsifiable. "Something weird is happening" is not a hypothesis.

### Step 3 — Write a Prove-It test

For non-trivial bugs, dispatch the `test-engineer` agent via `/test bug "<description>"`. The agent writes a failing test that captures the bug. Confirm the test fails for the right reason (matches the hypothesis).

For trivial bugs (typo, off-by-one) where the test would be more work than the fix, skip this step — but only with a brief comment in the explanation noting why.

### Step 4 — Confirm hypothesis with evidence

Read the code at the hypothesized location. Trace inputs and outputs. Confirm the bug is at the layer you think it is.

If the evidence contradicts the hypothesis, REFORMULATE. Don't proceed with a fix to the wrong layer. Common red flag: "the bug is in <file A> but the fix would be in <file B>" — that's usually a sign the hypothesis is wrong.

### Step 5 — Propose the fix

Refer to `.claude/rules/style.md` "No laziness / find root cause" — no temporary fixes, no patch-overs. The fix addresses the root cause, not the symptom.

For non-trivial fixes (>~30 lines, new abstraction, multi-layer), dispatch `elegance-challenger` before presenting. Surface the elegance challenge's alternative to the user even if you disagree with it.

### Step 6 — Verify

After the user accepts the fix:

```bash
composer test         # all tests including the Prove-It test now pass
composer phpstan
composer cs-check
```

Suggest committing via `/commit` — typically as TWO commits per the testing.md Prove-It pattern:
1. The failing test (commit before applying fix).
2. The fix.

If the test was already committed (good practice), the fix is one commit.

## Rules

- No fixes proposed without all four pieces of info gathered.
- Hypothesis is falsifiable, not vague.
- Root cause, not symptom. If the temptation is "wrap this in a try/catch and log", that's a patch-over — keep digging.
- Non-trivial fixes go through elegance-challenger before presentation.
- Two commits for bugs: Prove-It test, then fix.
