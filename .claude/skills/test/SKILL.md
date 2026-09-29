---
name: test
description: >
  TDD orchestrator. Dispatches to the test-engineer agent for coverage gap
  analysis or Prove-It bug tests. After the agent returns, runs the new test
  to confirm it behaves correctly (passes for new tests, fails for Prove-It).
---

# Test

Dispatches to `test-engineer`. This skill is a thin orchestrator.

## Arguments

Parse `$ARGUMENTS`:

- **No args** → ask the user what to test (a class? a feature? a bug?), then dispatch.
- **`<ClassName>`** (e.g. `/test UsersTable`) → coverage gap analysis for that class.
- **`bug "<description>"`** → Prove-It mode: agent writes failing test only, does NOT fix.

## Workflow

### Coverage gap mode (`/test <ClassName>`)

1. Spawn `test-engineer` agent with the class name.
2. Agent reads the class, identifies coverage gaps, returns a report.
3. Skill surfaces the report to the user, asks which gaps to fill.
4. Spawn `test-engineer` again with the chosen scope to write the tests.
5. Run `vendor/bin/phpunit --filter <NewTestName>` to confirm they pass.

### Prove-It mode (`/test bug "<description>"`)

1. Spawn `test-engineer` agent with the bug description.
2. Agent writes ONE test that demonstrates the bug. Returns the test file path and test method name.
3. Skill runs `vendor/bin/phpunit --filter <NewTestName>`.
4. Confirm the test FAILS for the right reason (matches the bug description).
5. If it passes (the bug doesn't reproduce), report back to the user — either the bug is fixed already, or the test doesn't capture it.
6. If it fails correctly, report: "Prove-It test ready at `<path>::<method>`. The test fails with: `<error>`. Ready for the fix implementation."

### Interactive mode (no args)

1. Ask: "What are you testing?" Offer options: a class, a feature, a bug.
2. If bug → switch to Prove-It mode.
3. If class → switch to coverage gap mode.
4. If feature → spawn `test-engineer` with the feature scope (controller + service + table tests).

## Composition

- This skill never writes tests itself. The agent does that.
- After the agent returns, this skill verifies behavior (run the test, check result).
- For non-trivial test design questions, this skill is the right entry point — not the agent invoked directly.
