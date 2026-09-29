# Lessons

Self-correction log. Append after every user correction. Read at session start.

If a lesson generalises beyond a single mistake, promote it to `.claude/rules/<topic>.md` and remove the entry here.

---

### 2026-05-24 — Seed entry: lessons go here, not in CLAUDE.md
**Trigger:** when the user corrects an approach, a pattern, or a habit.
**Do:** append a new dated entry below this seed using the format `### YYYY-MM-DD — <one-line rule>` followed by `**Trigger:** / **Do:** / **Why:** / **See:**`.
**Why:** the SessionStart hook auto-injects this file. If lessons live in CLAUDE.md or in agent prompts, they're stale immediately and don't survive a Claude version upgrade.
**See:** `CLAUDE.md` § Self-improvement loop.

### 2026-06-07 — In a Table, reach a belongsToMany junction/target via getAssociation(), not the magic `$this->Tags`
**Trigger:** writing a finder/method in a Table that needs the join table or target of a belongsToMany (e.g. a subquery over the polymorphic `taggables` junction).
**Do:** `$assoc = $this->getAssociation('Tags'); if (!$assoc instanceof BelongsToMany) { return $query; } $junction = $assoc->junction();` — import `Cake\ORM\Association\BelongsToMany`. The instanceof both narrows for PHPStan and is a genuine guard.
**Why:** the belongsToMany magic property `$this->Tags` is typed `mixed` by cakephp-phpstan → "Access to an undefined property" plus a cascade of "Cannot call method X() on mixed" (~12 errors per method at level max). `getAssociation()` returns a typed `Association`; the instanceof yields `BelongsToMany`, which has `junction()`.
**See:** `src/Model/Table/PagesTable.php::findByTags`, `PostsTable::findByTags`.

### 2026-05-24 — CakePHP PHPStan extension package is `cakedc/cakephp-phpstan`, not `cakedc/phpstan-cakephp`
**Trigger:** when adding CakePHP-aware PHPStan rules to a project.
**Do:** `composer require --dev cakedc/cakephp-phpstan:^4.0`. With `phpstan/extension-installer` also installed, no manual `includes:` line in `phpstan.neon` is needed — extension-installer auto-discovers the extension via composer plugin.
**Why:** The package was renamed; old name still appears in search results and AI training data. Manual `vendor/cakedc/.../extension.neon` includes are stale paths anyway — extension-installer 1.4+ handles discovery.
**See:** `phpstan.neon` (no `includes:` for vendor packages).

### 2026-05-24 — Disable Rector's `ThrowWithPreviousExceptionRector` in CakePHP projects
**Trigger:** running Rector on code that throws `Cake\Http\Exception\*` (NotFoundException, BadRequestException, etc.).
**Do:** Skip the rule in `rector.php`: `->withSkip([ThrowWithPreviousExceptionRector::class])`.
**Why:** CakePHP HTTP exception classes use their `$code` constructor argument as the HTTP response status (e.g. `NotFoundException` defaults to 404). The rector rule rewrites `throw new NotFoundException()` to `throw new NotFoundException($e->getMessage(), $e->getCode(), $e)`, which clobbers 404 with the previous exception's code (typically 0 or 500), breaking response status tests.
**See:** `rector.php` `withSkip` block; `tests/TestCase/Controller/PagesControllerTest::testMissingTemplate`.

### 2026-05-24 — Remove the dead `<ini name="apc.enable_cli">` line from CakePHP's `phpunit.xml.dist`
**Trigger:** new CakePHP 5 scaffold; running `composer test` and seeing a PHPUnit-runner warning that exits the test event with code 1.
**Do:** delete the line `<ini name="apc.enable_cli" value="1"/>` from `phpunit.xml.dist`. Installing APCu won't silence the warning — the directive is for the legacy APC extension (pre-PHP-7), not APCu, and APCu doesn't register `apc.*` ini settings.
**Why:** CakePHP's scaffold still ships the APC ini line from a decade-old template. APC is dead since PHP 7. Even with APCu installed, PHP can't set `apc.enable_cli` because no extension owns that ini key. The warning propagates as exit code 1 from the composer-script wrapper, breaking `composer quality` red even when 9/9 tests pass.
**See:** `phpunit.xml.dist` line 10 (memory_limit only); Dockerfile installs `apcu` for runtime caching but that's unrelated to this warning.

### 2026-05-24 — MS `devcontainers/php` base image has a broken Yarn apt source
**Trigger:** building `mcr.microsoft.com/devcontainers/php:1-8.4-bookworm` or any sibling tag; apt-get update fails with `NO_PUBKEY 62D54FD4003F6525` for `dl.yarnpkg.com`.
**Do:** add `rm -f /etc/apt/sources.list.d/yarn.list` as the FIRST command in the apt-handling `RUN` block in `Dockerfile`. Don't try to install the Yarn signing key — CakePHP doesn't need Yarn anyway.
**Why:** the base image preconfigures a Yarn apt source whose signing key has expired/rotated. Even though we don't install Yarn, `apt-get update` refuses to proceed when any configured source has unverifiable signatures. Removing the source line is the clean fix.
**See:** `.devcontainer/Dockerfile` line 5.

### 2026-05-31 — `filter_var(FILTER_VALIDATE_URL)` does NOT validate the scheme — allowlist it
**Trigger:** validating any user-supplied URL that will be rendered into an `href`/`src` (links, CTAs, redirects).
**Do:** allowlist the scheme explicitly — `parse_url($url, PHP_URL_SCHEME)` ∈ `{http, https, mailto}` (plus site-relative paths starting with a single `/`, rejecting protocol-relative `//`). Add a render-site guard too (drop the anchor for unsafe schemes) so legacy/bypassed rows can't fire.
**Why:** `FILTER_VALIDATE_URL` accepts `javascript://%0aalert(1)` (the `%0a` newline comments out the `//`), which `htmlspecialchars` does NOT neutralise in an `href` → stored XSS to public visitors. Found by security-auditor on the M3 CTA block.
**See:** `src/Model/Table/BlocksTable.php::isValidUrl`, `src/Service/Page/BlockExpander.php::isSafeUrl`; consider promoting to `.claude/rules/security.md`.

### 2026-06-07 — Logical-paragraph blank lines + readability passes in every method
**Trigger:** writing/editing any PHP or JS, especially a method that does setup → a run of `if`/loop steps → a final assembly step.
**Do:** separate logical paragraphs with a single blank line — e.g. after a query/setup line before a sequence of filter `if`s, and between each distinct step. Also: extract dense one-liners/expressions to named locals (e.g. `array_map(fn …, $query->all()->toList())` → name the list), prefer guard clauses/early returns, keep nesting ≤ 2. Never ship a wall of un-spaced statements.
**Why:** user flagged `PagesController::index()` as an unreadable block of back-to-back `if`s with no breathing room; CLAUDE.md "Code Style → Logical Paragraph" and `.claude/rules/style.md` require paragraphed, scannable code. php-cs-fixer does NOT insert these blank lines, so it's on me — apply it as I write, not as an afterthought.
**See:** CLAUDE.md (Code Style § Logical Paragraph), `.claude/rules/style.md`.

### 2026-06-07 — Add a `use` import in the SAME edit as its first usage (cs-fixer hook strips unused imports)
**Trigger:** introducing a new `use ...;` import to a PHP file as a standalone edit, before adding the code that references the symbol.
**Do:** put the import and its first usage in one edit, or add the usage edit first. Never add an import alone and rely on a later edit to use it.
**Why:** the PostToolUse php-cs-fixer hook runs `no_unused_imports` immediately after each edit. In `PostsControllerTest` I added `use Cake\ORM\TableRegistry;` / `use Cake\I18n\DateTime;` first, the hook deleted both as unused, then the test body referenced them → fatal "Class App\Test\TestCase\Controller\TableRegistry not found". Re-adding the imports after the usage existed fixed it.
**See:** `.claude/rules/php.md`.

### 2026-06-07 — Match an existing module's chrome by reading its templates BEFORE building a new module's UI
**Trigger:** building admin UI for a new feature/module (edit form, index list, sidebar) when sibling modules (Pages, Posts) already exist.
**Do:** before writing any template/CSS, open the reference module's templates and copy their exact structure: the save bar is a `<footer class="cms-savebar">` **sibling outside** `$this->Form->end()` (delete as a `cms-savebar__delete` postLink on the left, Cancel + Save right-aligned in `cms-savebar__actions`, submit via `form="<id>"`); sidebar cards are `<section class="cms-card cms-side-card"><header class="cms-side-card__header"><h2>` + `cms-card__field`/`cms-card__field-label`/`cms-card__control`/`cms-card__field-toggle`; list tables use the fixed 6-col `.cms-table` grid (`44px 2fr 1fr 1fr 1fr 100px`) with a leading checkbox cell — a 5-column table without the lead cell shifts every column into the 44px slot and wraps. Reuse existing classes; never invent (`.cms-side-card__body` doesn't exist).
**Why:** I built the Collections entry editor + index by eyeballing instead of reading `templates/element/admin/pages/form.php` and `templates/Admin/Posts/index.php`. Result: save bar nested in the form (didn't pin to bottom), Save/Cancel mis-ordered, Delete in the header, an unstyled Publish card (invented class), and both index tables cramped/wrapping. User called it "sloppy, lazy and broken." All of it was avoidable by reading the reference first.
**See:** `templates/element/admin/pages/form.php`, `templates/element/admin/pages/details-card.php`, `templates/Admin/Posts/index.php`, `.cms-table` grid in `webroot/css/admin.css`.

### 2026-06-07 — Posts/Pages orphan Taggables rows on delete; clean up new taggable types
**Trigger:** adding tags (the polymorphic Taggables junction) to a new content type, or touching Posts/Pages delete.
**Do:** on deleting a taggable record, explicitly `deleteAll` its Taggables rows (taggable_type + taggable_id + workspace_id) — `taggables` has NO FK to the owner table (it's polymorphic), so nothing cascades. CollectionEntries does this in `afterDelete` via `TagSyncer::clear`. PostsController/PagesController delete do NOT — they leave orphan Taggables rows (taggable_id pointing at a deleted post/page). That's a latent bug to fix there, not behavior to copy.
**Why:** elegance-challenger flagged that matching Posts/Pages' orphan behavior "for consistency" would propagate a bug; cleaning up is correct.
**See:** `src/Service/Collection/TagSyncer.php::clear`, `src/Model/Table/CollectionEntriesTable.php::afterDelete`.

### 2026-07-22 — The `php -S` dev server serves STALE bytecode after code changes; don't judge from one live request, and kill it by port-holder PID
**Trigger:** live-verifying an endpoint at `http://localhost:8765` (`bin/cake server` = PHP built-in server) right after editing PHP, or restarting that dev server.
**Do:** (1) Treat PHPUnit as the authoritative correctness gate — a hermetic passing test IS the proof; the live endpoint is only confirmation. (2) After a code change the server (opcache on, `revalidate_freq=2`) can serve stale/mixed bytecode for a few seconds — never conclude from ONE request; HAMMER the identical request ~8× (all-consistent = real, mixed = warmup flap). (3) To restart, find the real holder — `ss -ltnp | grep :8765` → `pid=N` → `kill N` — then confirm the port is FREE before relaunching and verify the NEW pid holds it. `php -S` DOES forward the `Authorization` header (it is never the cause of auth failures).
**Why:** ~1hr lost during GraphQL PAT verification chasing a "bug" that didn't exist — preview/mutations flapped (draft one call, null the next) purely from opcache staleness while PHPUnit stayed green. `pkill -f "php -S 0.0.0.0:8765"` made it worse: it (a) self-matches its own command line → the shell dies with exit 144, and (b) does NOT match an opcache-off server started as `php -d opcache.enable=0 … -S` (the `-d` flags break the contiguous `php -S` substring), so a stale server kept the port and every "restart" silently hit old code.
**See:** `.devcontainer/post-start.sh` (starts `bin/cake server`); `.claude/skills/playwright-cli`.

### 2026-07-22 — PHPUnit can't see JS mount/autosave regressions — browser-verify frontend behavior on merge
**Trigger:** merging/reviewing work that changes admin JS (widget mounts, autosave, editor wiring) or extracts shared JS/templates that other screens also use.
**Do:** after the PHP gate is green, run a real browser pass (Playwright / container chromium) on the actual flow — for autosave-driven editors, edit a field, await the autosave PATCH, reload, assert it persisted; also regression-load the OTHER screens that share the extracted code while watching `console`/`pageerror`. The extension-free container chromium (`chromium.launch()`) is the clean signal, not the Mac Chrome (its extensions throw `appendChild` noise).
**Why:** M5 shipped a rich-text custom field that silently did NOT autosave on an existing page — the shared lite editor flushed to its textarea only on form `submit`, and the page editor autosaves via `FormData` without a submit. 583 PHPUnit tests were green and never touched it; the browser define-then-fill check caught it. Fix was to flush on `editor.on('update')` + dispatch `cms:autosave`.
**See:** `webroot/js/admin/field-widgets.mjs::mountRichText`, `.claude/rules/frontend.md`.

### 2026-09-28 — Parallel agents in git worktrees never use `git stash`; park work in a WIP commit on the agent's own branch
**Trigger:** several agents (or people) working in separate `git worktree`s of one repo, and one of them wants to set uncommitted changes aside (to switch branches, re-run a baseline, or rebase).
**Do:** commit the work in progress on your own branch (`git commit -m "wip: ..."`, then amend or squash later) instead of `git stash`. Never `git stash` / `git stash pop` in a shared-repo worktree.
**Why:** `refs/stash` lives in the common git dir, so every worktree of a repo shares one stash stack. Two agents' stash/pop swapped each other's uncommitted work — each popped the other's changes into its own tree. A WIP commit is scoped to the branch that owns it.
**See:** `git rev-parse --git-common-dir` — the one directory every worktree shares, which holds `refs/stash`.
