# Frontend — `webroot/` House Style

The admin UI is server-rendered CakePHP templates enhanced by vanilla ES modules served straight from `webroot/js/` — **no build step for app code**. The framework is these conventions plus a small shared kit under `webroot/js/admin/kit/`. A contributor learns it by reading this file and the kit's `http.mjs`.

Read this before touching anything under `webroot/`.

## Module kinds

| Kind | Location | Loads how | Side effects |
|---|---|---|---|
| Kit | `webroot/js/admin/kit/*.mjs` | `import` only | None |
| Page module | `webroot/js/admin/*.mjs`, `auth/*.mjs`, `workspace/*.mjs` | `$this->append('script')` from the element that renders its markup | Mounts on `[data-*]` roots |
| Vendor | `webroot/js/admin/vendor/` | Built by esbuild, or copied from npm | `tiptap.entry.js` is the **build input**; `tiptap.bundle.mjs` is the **committed artifact** (`npm run build:editor`). `vendor/graphiql/` is copied verbatim from the exact-pinned `graphiql`, `react` and `react-dom` packages by `npm run vendor:graphiql`. CI re-runs both and fails if the committed files differ. |

- The `.js` extension marks an esbuild input; every served module is `.mjs`. Never load a `.js` directly in a template.
- A module `<script>` gets its `src` from `$this->Url->assetUrl('/js/…mjs')`, never `Url->build()` or `Url->script()`: `assetUrl` appends the file's mtime (`Asset.timestamp` is `force`, so it applies in production too), while `Url->script()` would append `.js` to a `.mjs` path. Stylesheets go through `Html->css()`, which timestamps on its own. Only the entry URL is busted; a module's own `import` URLs are not, so `webroot/.htaccess` sends `Cache-Control: no-cache` for every `.mjs` (the browser keeps the file but revalidates, and Apache answers unchanged files with a 304) and declares `text/javascript` for `.mjs`, which older Apache MIME tables lack and which a module script cannot run without. Mirror both on any non-Apache server.
- `main.mjs` + `flash.mjs` load on every admin page via `templates/layout/admin.php`; every other page module is pulled in by the template that renders its markup.

## The kit

One seat per primitive. Import from the kit; never re-implement.

| Module | Exports | Contract |
|---|---|---|
| `kit/http.mjs` | `ADMIN_BASE`, `getJson`, `postForm`, `postUpload`, `postJson`, `patchForm`, `postAction` | The single XHR seat. Paths are **workspace-relative** (`'/pages/reorder'`) — the kit prefixes `ADMIN_BASE` (a path already under it passes through). Every mutating verb sends `X-CSRF-Token` from `meta[name=csrf-token]` and runs through one internal `request(method)`; `postForm`/`patchForm` encode `URLSearchParams` — `PATCH` never sends `FormData` (CakePHP drops multipart PATCH) — and `postUpload` sends `FormData`. `getJson(path, { signal }?)` takes an `AbortSignal` so a newer request can cancel a stale one (the media picker aborts its in-flight page on a new search). One `send` surfaces 429 + session-expiry as toasts for every verb; JSON verbs return `null` on `204` and throw an `Error` otherwise — callers catch and decide UX, never swallow to `[]`. `postAction(path, body?)` is the mutate-and-reload verb for redirect-back endpoints (moderation, save-tree): same backstop, returns `response.ok` (`false` once the session has ended). |
| `kit/dom.mjs` | `el`, `svgIcon`, `debounce`, `formatBytes`, `showToast` | DOM building and the one parchment toast (a manual `popover`, re-shown per toast so it paints above an open modal). `el(tag, className?, text?)`. `svgIcon(markup)` turns a static SVG string into a fresh element through `DOMParser`, so an icon constant can be appended any number of times and nothing in it can execute; it is the seat for icon markup, so modules never assign a dynamic string to `innerHTML`. |
| `kit/modal.mjs` | `createModal` | The shared modal shell on a native `<dialog>` opened with `showModal()`: top layer, `::backdrop`, native Escape (closes only the topmost dialog), a press that starts on the backdrop closes it, and `open({ onClose })` fires on the dialog's `close` event. Every modal — media library/picker, reference picker, image/link editors, block picker — goes through it; simple yes/no questions use `window.confirm()`. Reuse one instance via the lazy-singleton idiom (`modal ??= createModal()`). |
| `kit/drag.mjs` | `makeSortable` | `makeSortable(list, {itemSelector, draggingClass, beforeClass, afterClass, intoClass?, zoneWithin?, onDrop})`: native drag-and-drop sorting that commits on drop. It owns the dragging class, the move effect and the drag payload; sorting starts only from an element drag (`draggable`), never a text-selection drag. |
| `kit/slug.mjs` | `slugify`, `machineName`, `bindSlugFollow` | One slug ruleset. `bindSlugFollow(sourceInput, slugInput)` mirrors source→slug until the slug is manually edited. `machineName` is the strict valid-identifier form (`f_` prefix, 50-char cap). |
| `kit/suggest.mjs` | `attachSuggest` | The one debounced-fetch autocomplete. `attachSuggest(input, panel, {fetchItems, onPick, onCreate?, activateFirst?})` renders each item's `label` as a `cms-tag-suggest__item` button with `is-active` keyboard nav and mousedown-pick, and hides the panel on blur, Escape and pick — callers don't re-implement that. Returns `{ hide }` for callers that clear the input themselves. Panel must be a container that accepts button children (a `<div>`, not `<ul>`). |

`main.mjs` is the layout kernel: it registers the one delegated behaviour (`[data-autosubmit]` change-submit) and imports nothing. Its old fetch/URL facade is gone — **all HTTP goes through `kit/http`.**

## Conventions

- **DO** select mount points with `[data-*]` attributes; **NEVER** select by `cms-*` class. Classes are for CSS only. ESLint fails the gate on any `.cms-` selector literal in JS, including the `itemSelector` / `zoneWithin` options handed to `kit/drag`.
- **DO** style with `cms-*` BEM classes; **NEVER** drive behavior off a class. New UI reuses the existing `cms-*` vocabulary — do not invent chrome.
- **DO** carry a feature's config as sibling `data-*` attributes on its mount root, read from that node's `dataset`. A page module finds its root with `document.querySelector('[data-…]')` and does nothing when it is absent.
- **DO** reach endpoints via a server-provided `data-*-url` / `data-*-base` attribute on the mount node (built with `h($workspacePath . ...)` or `$this->Url->build`). The kit's workspace-relative paths are the fallback. **NEVER** hardcode an admin path literal (`/admin/...`) in JS — it 404s under multi-tenancy.
- **DO** register every new admin AJAX endpoint's `[Controller, action]` in `RateLimitMiddleware::GENEROUS` **in the same commit** that adds the fetch. The default tier 429s bursty XHR; the kit now surfaces that as a toast instead of a silent drop.
- **DO** flush complex widget state to hidden inputs on submit (schema JSON, tag/reference `name[]` inputs, tiptap textarea writeback). Booleans always submit (hidden-`0` + checkbox/aria-pressed-`1`).
- **DO** pass payloads too large for an attribute through a child `<script type="application/json">` island on the mount node (`JSON_HEX_*` flags server-side), merged with `dataset`.
- **DO** name cross-module signals as `cms:`-prefixed CustomEvents with the payload in `detail`. No event bus — two events do not justify one.
- **NEVER** write code comments; names carry meaning. **NEVER** treat the tiptap client schema as a security boundary — the server `BodySanitizer` at `beforeSave` is the control.
- **NEVER** centralize prematurely: an abstraction must stay smaller than the duplication it removes. Three similar lines beat a premature framework.

## Tooling

`npm run lint` is the frontend gate (Prettier check, ESLint, Stylelint over `webroot/js` and `webroot/css`); `npm run lint:fix` applies every autofix. CI runs the gate, then rebuilds the editor bundle from the committed lockfile and fails if `tiptap.bundle.mjs` differs. The `PostToolUse` auto-format hook runs Prettier (and `stylelint --fix` for CSS) on every edited file under those two roots.

- **Prettier** (`.prettierrc`): 4-space indent, single quotes, 120 columns. CSS is one declaration per line — the packed "several properties per line" style is retired because no tool can enforce it.
- **ESLint** (`eslint.config.js`): `js.configs.recommended` plus `no-console`, and two house rules, all errors: no string literal may contain a `.cms-` selector, and `innerHTML` may only be assigned a string literal or an `UPPER_CASE` constant (static icon markup). Anything built from data goes through `el()`, `textContent` or `svgIcon()`; a server `<template>` is cloned via `template.content`.
- **Stylelint** (`.stylelintrc.json`): `stylelint-config-standard`, alphabetical property order via `stylelint-order`, and a BEM `selector-class-pattern` that also admits the ProseMirror vendor classes. `no-descending-specificity` is off: every hit was the intentional `.parent:hover .child` before `.child:hover` pattern, and `@layer` already fixes cascade order between files.
- The minified vendor files (`vendor/graphiql/`, `tiptap.bundle.mjs`) are excluded; `tiptap.entry.js` is linted and formatted as source.

## Data-attribute & event registry

The template↔JS contract. **Owner** is the template that emits the attribute (or the JS module that creates the node at runtime, marked *created in JS*); **Consumer** is the JS module (or CSS/native) that reads it. Keep this table honest when adding or removing a `data-*` hook.

| Attribute / Event | Owner (emits it) | Consumer (reads it) | Purpose / payload |
|---|---|---|---|
| `cms:autosave` (event) | `page-editor.mjs` (dispatch) | `page-editor.mjs` (listen) | Schedule a debounced autosave PATCH. |
| `cms:block-media` (event) | `block-media.mjs` (dispatch, `detail`=item) | `block-preview.mjs` | A media slot was picked; preview repaints from `detail`. |
| `meta[name=csrf-token]` | `layout/admin.php` | `kit/http.mjs` | CSRF token for mutating XHR. |
| `meta[name=workspace-path]` | `layout/admin.php` | `kit/http.mjs` (`ADMIN_BASE`) | Tenant path prefix for every admin URL. |
| `data-add-field` | `field-schema/builder.php` | `schema-builder.mjs` | "Add field" button. |
| `data-add-tag` | `pages/tags-card.php` (shared by the page + post editors) | `page-editor.mjs` | Reveals the tag input. |
| `data-author-*` (`-avatar`/`-card`/`-check`/`-id`/`-name`/`-option`/`-picker`/`-sub`) | `pages/author-card.php` (shared by the page + post editors) | `page-editor.mjs` | Author picker card nodes; each option carries `data-id`/`data-name`/`data-sub`/`data-style` for the card to copy, and `data-author-check` marks the tick the picker rewrites. The picker is a native `popover` opened by the card's `popovertarget` button (light dismiss + Escape are native) and anchored under the card with CSS anchor positioning. |
| `data-autosave` | `pages/form.php` | `page-editor.mjs` | Autosave flag on the editor form. |
| `data-autosubmit` | `Admin/Users/index.php`, `Admin/Blocks/index.php` | `main.mjs` | On `change`, submit the enclosing form. |
| `data-block` | stored body HTML / tiptap | `page-editor.mjs`, tiptap bundle | Marks an embedded block node in rich text. |
| `data-block-form` / `data-block-preview` | `blocks/form.php` | `block-preview.mjs` | Block form + live-preview stage. |
| `data-bulkbar` / `data-bulk-count` / `data-bulk-clear` / `data-select-all` | `Admin/Pages/index.php` | `pages-index.mjs` | Bulk-select bar. |
| `data-cardinality` / `data-target` / `data-input-name` / `data-initial` | `collections/field-widget.php` | `field-widgets.mjs` | Reference/tag field config. |
| `data-collections-media` / `-ref` / `-tags` / `-tiptap` | `collections/field-widget.php` | `field-widgets.mjs` | Entry-editor widget mounts (shared by the collections + page/post editors). |
| `data-comment-*` / `data-comments*` / `data-reply-*` | `Admin/Comments/index.php` | `comments.mjs` | Moderation rows, bulk actions, reply forms. |
| `data-details-*` (`-card`/`-edit`/`-fields`/`-read`) | `pages/details-card.php` | `page-editor.mjs` | SEO/details card. |
| `data-graphiql` / `data-endpoint` / `data-token-endpoint` | `Admin/ApiPlayground/index.php` | `graphql-playground.mjs` | GraphiQL mount; `GraphiQL.createFetcher` posts to the endpoint (header-editor headers merged in), the token button `postJson`s to the token endpoint. |
| `data-dismiss-flash` | `element/flash/*.php` | `flash.mjs` | Click-to-dismiss a flash. |
| `data-empty-hint` / `data-field-list` / `data-field-types` / `data-fields-in-use` / `data-schema*` | `field-schema/builder.php` (mount + `data-schema`); `collections/schema-form.php`, `ContentFields/edit.php` (the `data-schema-form` / `data-schema-input` form host); `data-schema-field` / `data-handle` / `data-label-input` *created in JS* | `schema-builder.mjs` | Schema builder state, shared by the collection + Pages/Posts field editors; `data-schema-field` marks each sortable row. |
| `data-media-count` | `Admin/Media/index.php` | `media.mjs` | Library file-count label, bumped on upload/delete. |
| `data-media-grid` / `data-media-id` / `data-upload-input` / `data-upload-trigger` | `Admin/Media/index.php`; uploaded cards *created in JS* by `media-ui.mjs` | `media.mjs`, `media-picker.mjs` | Media library grid + upload. `data-media-id` is how a card is found inside a grid (library and picker alike); the upload tile is the grid's `data-upload-trigger`. Cards also carry `data-kind` / `data-name`, which nothing reads. |
| `data-ext` | `Admin/Media/index.php`, `media-ui.mjs` | **CSS** (`content: attr(data-ext)`) | Extension label on a document card. |
| `data-media-alt` | *created in JS* (`media.mjs`) | `media.mjs` | Alt-text field in the media detail modal. |
| `data-media-choose` / `-clear` / `-preview` | `collections/field-widget.php` | `field-widgets.mjs` | Media widget in entry editor. |
| `data-nav-*` (tree, add/save, per-node) / `data-menu-id` | `Admin/Navigation/index.php` + *created in JS* | `navigation.mjs` | Menu tree editor; `data-menu-id` marks each menu tab. Per node (*created in JS*): `data-nav-id` on the item, `data-nav-row` / `data-nav-handle` / `data-nav-label` / `data-nav-path` inside it, `data-nav-hint` under the details form. |
| `data-nav-data` | `Admin/Navigation/index.php` (`<script type=application/json>`) | `navigation.mjs` | Menu tree bootstrap island. |
| `data-page-id` / `data-pages-tree` / `data-reorder-url` / `data-parent-id` / `data-tree-row` / `data-tree-check` / `data-tree-children` | `pages/*`, `pages/tree_row.php`, `Admin/Pages/index.php` | `pages-index.mjs`, `page-editor.mjs` | Reorder / autosave / revisions. A tree node is `[data-page-id]`; its `data-tree-row` is the drag zone, `data-tree-check` the bulk-select box, `data-tree-children` the nested list. |
| `data-pages-sidebar` / `data-revisions-card` / `data-restore-version` / `data-saved-indicator` | `pages/*` | `page-editor.mjs` | Editor sidebar, revisions, save status. |
| `data-placeholder` | `pages/title_field.php`, `collections/entry-title.php`, `collection-title.php` | **CSS** (`content: attr(...)`) | Empty contenteditable-title placeholder. |
| `data-preview-*` / `data-empty` | `blocks/preview.php` | `block-preview.mjs` + **CSS** | Block live-preview bindings; `data-empty` flags a media block with no media yet. |
| `data-ref-choose` / `-inputs` / `-pills` | `collections/field-widget.php` | `field-widgets.mjs` | Reference picker. |
| `data-repeater*` | `field-schema/fields.php`, `collections/repeater-row.php` | `field-widgets.mjs` | Repeater rows + `__INDEX__` template clone. |
| `data-media-slot` / `data-kind` / `data-slot-*` (`-choose`/`-input`/`-name`/`-sub`/`-thumb`) | `blocks/fields.php` | `block-media.mjs` | Block media slots; `data-kind` picks the picker filter. |
| `data-tag-*` / `data-tags-*` / `data-slug` | `tag_filter.php`, `pages/tags-card.php`, `field-widget.php` | `tag-filter.mjs`, `page-editor.mjs`, `field-widgets.mjs` | Tag chips (each chip's `data-slug`), suggest, attach/detach. On the editors' tags card a chip is `[data-tag-slug]` and its remove button `data-tag-remove` (template and JS-created alike). |
| `data-tiptap` / `data-collections-tiptap` | `pages/form.php`, `posts/form.php`, `field-widget.php` | `page-editor.mjs` (`data-tiptap`), `field-widgets.mjs` (`data-collections-tiptap`) | Rich-text mount. |
| `data-title-input` / `data-title-edit` / `data-title-target` | `*/title_field.php`, `entry-title.php`, `collection-title.php`, `Posts/add.php`, `edit.php` | `inline-title.mjs`, `page-editor.mjs` | Contenteditable title ↔ hidden input + slug follow. |
| `data-toggle-password` | `Users/login.php` | `auth/login.mjs` | Password reveal. |
| `data-workspace-name` / `data-workspace-slug` | `Workspaces/add.php` | `workspace/new.mjs` | Workspace name → auto-slug. |

### Contract breaks — resolved in Step 3

Dead attributes removed from templates (nothing read them): `data-field` (`Users/login.php`), `data-field-path` (`collections/repeater-row.php`, `entry-form.php`), `data-name` on the repeater (`entry-form.php`), `data-slot-rendition` (`blocks/fields.php`), `data-tags` (`pages/`+`posts/tags-card.php`), `data-workspace-switcher` (`WorkspaceSwitcher` cell).

Dangling queries fixed:

- `data-media-count` — added to the count node in `Admin/Media/index.php`, so `media.mjs` now updates the library count on upload/delete.
- `data-footer-text` / `data-footer-link` — the login footer is a hard link to `/signup` and the page has no register tab, so the dead footer-swap and unreachable `register` copy were removed from `auth/login.mjs` (not wired to hooks that shouldn't exist).

## Migration status

House Style lands incrementally; every step ships independently and leaves the app fully working.

- **Done — Step 1 (this file):** conventions spec + registry.
- **Done — Step 2:** kit landed (`http`, `dom`, `mount`, `modal`, `slug`); `main.mjs` and `media-ui.mjs` route through it (single `ADMIN_BASE`/`csrfToken` seat; modal Escape-leak fixed — one handler closes only the top of the stack).
- **Done — Step 5:** all slug generation converges on `kit/slug` — `inline-title`, `workspace/new`, page-editor + collections-editor tag slugs (`slugify`, which trims dashes so `-x-` can't slip past server validation), and schema-builder field names (`machineName`, strict valid-identifier form: `f_` prefix, 50-char cap).
- **Done — Step 3:** contract breaks fixed (see resolved list above); dead attributes removed.
- **Done — Step 4:** every admin module routes fetches through `kit/http` (comments, pages-index, block-media/preview, schema-builder, navigation, collections-editor, media, media-picker, page-editor). Redirect endpoints (media delete, page restore, comment moderation) use `postAction`; a stuck-"Saving…" autosave badge now reads "Not saved" on failure; the media-delete "Could not delete"-on-success bug is fixed.
- **Done — Step 11:** `main.mjs` shrunk to the layout kernel (no importers). `auth.css` de-forked onto `tokens.css` — its duplicate `:root` is gone, login/signup load `['tokens', 'auth']`, and the accent reconciles to admin's blue (`#4a6fa5`, the chosen winner). Verified: auth `--accent` now resolves to admin's, `--ink` unchanged, login works.
- **Done — Step 6:** `tokens.css` holds the admin design tokens (the `:root` block, moved verbatim) plus a named z-scale (`--z-savebar` 20, `--z-popover` 30, `--z-dropdown` 50, `--z-modal` 1000, `--z-toast` 1100); loaded before `admin.css`. z-index literals substituted to tokens byte-for-byte, and the one deliberate fix: `.cms-filter__panel` moves from `z-index:10` (hidden under the savebar) to `var(--z-popover)`. `auth.css` still forks its own tokens (its `--accent` differs from admin's — reconciling it is the human-eye decision deferred to the auth de-fork).
- **Done — Step 7:** the admin CSS is organised into `@layer reset, base, layout, components, surfaces` (the layer names replace the old comment banners — no code comments) and split into one file per layer under `webroot/css/admin/` (`reset|base|layout|components|surfaces.css`). `tokens.css` declares the layer order (`@layer reset, base, layout, components, surfaces;`) and loads first, so cascade order is fixed by declaration, not file load order. The layout loads `['tokens', 'admin/reset', 'admin/base', 'admin/layout', 'admin/components', 'admin/surfaces']`. Verified cascade-neutral by a computed-style diff (every resolved style byte-identical across surfaces; the only diffs are text-element widths that track live content like the page title and relative timestamps).

### CSS file layout

No file exceeds ~600 lines. `tokens.css` declares `@layer reset, base, layout, components, surfaces;` and loads first, so cascade precedence is set by that declaration — file load order (and file count) can't change it, which is what makes the split safe. The layout loads all sheets explicitly (no `@import`, no build).

| File | Layer | Holds |
|---|---|---|
| `tokens.css` | — | Design tokens (`:root`), z-scale, the `@layer` order declaration. First on admin + auth. |
| `admin/reset.css` | `reset` | Box-sizing, bare-element resets, and the one `[hidden] { display: none !important; }`. Also loaded by `layout/auth.php` (`['tokens', 'admin/reset', 'auth']`), so `auth.css` carries no reset of its own. |
| `admin/base.css` | `base` | `body.cms-app`, typography. |
| `admin/layout.css` | `layout` | Topbar, sidebar, breadcrumb, page-header, body/main frame. |
| `admin/components/controls.css` | `components` | Buttons, tables, pills, form-fields, cards. |
| `admin/components/editor.css` | `components` | Editor chrome, toolbar, image tools, aside cards, savebar. |
| `admin/components/widgets.css` | `components` | Media grid, tag/chip/filter widgets, suggest. |
| `admin/components/blocks.css` | `components` | Block library/cards/form. |
| `admin/components/overlays.css` | `components` | Modal, confirm, publish, flash. |
| `admin/surfaces/editor.css` | `surfaces` | Pages tree, editor layout, author picker. |
| `admin/surfaces/schema.css` | `surfaces` | Collection schema builder. |
| `admin/surfaces/content.css` | `surfaces` | Repeater, media detail, revisions, block editor, picker. |
| `admin/surfaces/comments.css` | `surfaces` | Comment moderation. |
| `admin/surfaces/nav.css` | `surfaces` | Navigation builder + settings. |
| `admin/surfaces/users.css` | `surfaces` | Users roster + account pages. |

A rule's cascade weight comes from its `@layer`, so put each rule in the file whose layer it belongs to — a reusable widget in a `components-*` file, a per-screen rule in a `surfaces-*` file. The split within a layer (`components-*`, `surfaces-*`) is by cohesion only; since they share a layer, load order among them still tie-breaks equal-specificity rules, so keep the layout's load order stable.
- **Done — Step 8:** all three tag widgets use `kit/suggest` — tag-filter (`activateFirst`, Enter picks the highlighted match), page-editor and collections (`activateFirst: false` + `onCreate`, Enter creates the typed tag, arrows pick a suggestion). The `data-suggest-slug` attribute is retired (kit closes over the item). Verified: Enter-creates and click-pick paths work, no JS errors in the extension-free chromium.
- **Done — Step 9:** `kit/drag` (sortable, commit-on-drop, optional `zoneWithin`/`intoClass`) adopted by schema-builder, the pages tree, the collections repeater, and the navigation tree. `navigation.mjs` render is rewritten from `innerHTML` strings to `el()`/`textContent` — user data (labels, urls, paths) now sets `textContent`/`value`, only static SVG icons keep `innerHTML`, and `esc()` is gone. Verified DOM-equivalent (classes/text/data-attrs identical before and after) with drag, select, live label edit, and save all working.
- **Done — Step 10:** `rich-text.mjs` (`mountRichText`) is the shared tiptap mount + toolbar-with-active-sync + writeback; page-editor (full: blocks, images, tables, autosave) and collections-editor (lite) both build on it, passing their own extensions/toolbar/classes. Net fewer lines than the two forks it replaced. Verified: both editors mount, bold applies, page-editor autosave fires, no errors.
- **Done — Step 12 (over-engineering cleanup):** modals run on native `<dialog>` (the `openModals` stack, document Escape listener and overlay div are gone; `.cms-modal[open]` + `::backdrop` replace `--open` + `__overlay`, and `--z-modal`/`--z-toast` retire because the top layer orders them). The page editor's block picker uses `createModal()`; schema-builder's "This field has data" modal is a `window.confirm()`. The link editor closes before refocusing the editor, since closing a dialog restores focus to its opener. Blocks and images insert via `appendAtDocumentEnd`: a modal clears the live selection, and the document end round-trips through save/reload without ProseMirror re-normalising it. The menu builder's link-type/target pickers are native `<input type=radio>` groups (the checked option styles via `:has(:checked)`) fed through the same `[data-nav-field]` listener, the tree takes one delegated click listener, and ids are stringified by a `JSON.parse` reviver (so page ids compare as strings).

Note: the dev Mac Chrome injects browser-extension content scripts that throw `appendChild` errors on every page — check JS-error sensitivity in the extension-free container chromium (`chromium.launch()`), not over CDP to the Mac Chrome.

Guard every step with the E2E suite (`tests/E2E/` and the CRUD/schema/sidebar checks) plus a screenshot pass for CSS steps.
