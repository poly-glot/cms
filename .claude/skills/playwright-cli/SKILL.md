---
name: playwright-cli
description: >
  Drive a real browser against the running CakePHP app at http://localhost:8765
  for end-to-end checks, page screenshots, and recording flows with codegen.
  Use for multi-step user journeys that span pages and forms; use PHPUnit
  IntegrationTestTrait for single-action HTTP assertions instead.
---

# Playwright CLI

Headless Chromium (installed in the devcontainer) for browser automation. Driven via the `playwright` CLI globally installed at `/usr/lib/node_modules/@playwright/test/`. Browsers live at `$PLAYWRIGHT_BROWSERS_PATH=/ms-playwright` so the cache survives container rebuilds when the volume is preserved.

## When to use

| Scenario | Use Playwright? |
|---|---|
| Multi-step user journey (sign in → create record → verify it appears) | **Yes** |
| Verify JavaScript-rendered behaviour (alpine, htmx, vanilla JS) | **Yes** |
| Snapshot a page for visual review | **Yes** |
| Recording a flow you're about to test (`codegen`) | **Yes** |
| Asserting a single response status / body shape | **No** — use `IntegrationTestTrait` (faster, no browser) |
| Testing model logic | **No** — unit-test the Service / Table |
| Verifying validation errors render | Either — prefer integration if the message is server-rendered |

## When NOT to use

- The CakePHP `IntegrationTestTrait` can `post()`, `assertResponseOk()`, `assertResponseContains()`, and assert flash messages — all faster than launching a browser. Default to it unless you specifically need to exercise JavaScript or visual layout.

## Project layout

E2E tests live in `tests/E2E/`. Config in `playwright.config.ts` at workspace root (create when adding the first test). The `tests/E2E/` directory is excluded from PHPUnit via `phpunit.xml.dist`'s `<source>` block (testsuite already only picks up `tests/TestCase/`).

```
tests/
├── E2E/
│   ├── pages/                # Page Object Model classes
│   ├── fixtures/             # Test data helpers
│   └── *.spec.ts             # Test files
├── Fixture/                  # PHPUnit fixtures (untouched)
└── TestCase/                 # PHPUnit tests (untouched)
```

## Running

The CakePHP dev server is started automatically by `post-start.sh` at `http://localhost:8765`. Tail logs with `tail -f /tmp/cake.log` if a flow misbehaves.

```bash
# Run all E2E tests (headless)
playwright test

# Run a specific spec
playwright test tests/E2E/login.spec.ts

# Run with the inspector / debugger
PWDEBUG=1 playwright test tests/E2E/login.spec.ts

# Generate test code by recording a flow
playwright codegen http://localhost:8765
# or via alias: pwcodegen

# Open the app in the Playwright UI
playwright open http://localhost:8765
# or via alias: pwopen

# Screenshot the home page
playwright screenshot http://localhost:8765 /tmp/home.png

# Show the trace from the last failed test
playwright show-trace test-results/<name>/trace.zip
```

## First-time scaffold

When a test is needed and `playwright.config.ts` doesn't exist yet:

```bash
playwright init --quiet
```

Then trim the generated config to:

```ts
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/E2E',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI ? 'github' : 'list',
  use: {
    baseURL: 'http://localhost:8765',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});
```

Add `tests/E2E/` and `playwright.config.ts` to git; gitignore `test-results/`, `playwright-report/`, and `.last-run.json`.

## Conventions

- **Page Object Model.** Wrap each page in a class under `tests/E2E/pages/`. Selectors stay in one place; tests stay readable.
- **`data-testid` over CSS classes.** Add `data-testid="foo"` to elements you intend to query from tests. CSS classes are presentation-layer; testids are contract.
- **One concept per test.** Mirror the `testing.md` rule for PHPUnit.
- **Fixtures via direct DB seed**, not via clicking through registration. Speed matters in E2E. Use `bin/cake seeds run InitialSeed` or raw inserts.
- **No `page.waitForTimeout(ms)`.** Use `page.waitForSelector` / `expect(...).toHaveText(...)` — they auto-retry.
- **Screenshots on failure only.** Configured via `screenshot: 'only-on-failure'`. Don't commit screenshots — they go in `test-results/` which is gitignored.

## Composition

- Invoke `/playwright-cli` (this skill) when you need to drive the browser ad-hoc, scaffold the first E2E test, or record a flow with codegen.
- For new test design, dispatch the `test-engineer` agent with explicit E2E framing — same Prove-It pattern applies when reproducing a UI bug.
- `/quality` does NOT run Playwright by default (browser startup cost). Run the E2E suite separately with `npm run test:e2e`.

## Browser support

Only **Chromium** is installed by default to keep image size down. To add Firefox / Webkit:

```bash
sudo apt-get update && sudo apt-get install -y --no-install-recommends \
  libdbus-glib-1-2 libxslt1.1 libwoff1 libvpx7 libevent-2.1-7 libopus0 \
  libgstreamer-plugins-base1.0-0 libgstreamer-gl1.0-0 libgstreamer-plugins-bad1.0-0 \
  libwebpdemux2 libharfbuzz-icu0 libenchant-2-2 libsecret-1-0 libhyphen0 \
  libmanette-0.2-0 libnghttp2-14 libpsl5 libgles2
playwright install firefox webkit
```

Then update `playwright.config.ts` `projects:` array to include them.
