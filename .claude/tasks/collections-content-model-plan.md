# Collections → Content Fragments: Phased Implementation Plan

> Status: design synthesis, critic-reconciled. Storage architecture is **Candidate C (Hybrid)** — JSON-first values, one FK-backed reference junction. Every milestone below is built around it. This plan is the deliverable; execute milestone by milestone. Each milestone is PR-sized; all are independently shippable **except M-C6, which requires C4 + C5** (resolver + singleton helper).

---

## 1. VISION

Collections becomes the place where a CMS author defines their own content **types** — Team, FAQ, Support Contact, Site Settings — exactly the way Post and Page are types, except authored in the admin UI instead of by a developer. Modeled on Adobe AEM Content Fragments, a `Collection` is the **model** (an ordered, typed `field_schema`) and a `CollectionEntry` is an **instance**; the schema is the immutable contract every entry is validated against. v1 ships a working drag-and-drop **schema builder**, richer field types with real widgets (rich-text reusing Tiptap, media reusing the picker, choice lists, date/time), an inline **repeater** (AEM multifield) one level deep, **references** to entries of other collections (single + many, with DB-backed integrity, reverse lookup, and a depth-capped cycle-safe resolver), and a first-class **singleton** mode that AEM only leaves to convention. Entries are consumed three ways from one resolver: a public route, an embed inside Page/Post bodies via the existing block rail, and a read-only JSON API.

**Shippability nuance:** M-C1…M-C5 and M-C7 are each PR-sized and independently shippable. M-C6 is PR-sized but **not** standalone — it requires both M-C4 (the resolver + DTOs) and M-C5 (the singleton helper seam). Treat "independently shippable" as "PR-sized; M-C6 requires C4+C5."

---

## 2. TARGET DATA MODEL (Candidate C — copy-pasteable)

### 2.1 `field_schema` JSON — the type (on `collections.field_schema`)

Recursive node list. New keys are **additive** — a pre-M8d flat `{name,label,type,required}` row stays valid. Repeater recurses **one level only**; references are leaves carrying config; deeper composition is expressed by referencing a sub-collection (AEM's recommended pattern).

```jsonc
// Worked example: "Team member" collection
[
  { "name": "name",  "label": "Name",  "type": "text",      "required": true },
  { "name": "bio",   "label": "Bio",   "type": "rich_text", "required": false },
  { "name": "photo", "label": "Photo", "type": "media",      "required": false,
    "mediaKind": "image", "multiple": false },

  { "name": "skills", "label": "Skills", "type": "repeater", "required": false,
    "min": 0, "max": 20,
    "fields": [                                  // depth-1 only: no repeater/reference inside
      { "name": "label", "label": "Skill", "type": "text",   "required": true },
      { "name": "level", "label": "Level", "type": "select", "required": true,
        "multiple": false,
        "options": [
          { "value": "beginner",     "label": "Beginner" },
          { "value": "intermediate", "label": "Intermediate" },
          { "value": "expert",       "label": "Expert" }
        ] }
    ] },

  { "name": "manager",  "label": "Manager",  "type": "reference",
    "target": "team",    "cardinality": "one",  "onDelete": "setNull" },
  { "name": "projects", "label": "Projects", "type": "reference",
    "target": "project", "cardinality": "many", "onDelete": "restrict", "min": 0, "max": 10 }
]
```

**Node grammar (canonical):** `name` matches `^[a-z][a-z0-9_]{0,49}$`; unique among siblings; not in `RESERVED` (§7). `label` is free-form (escaped on output). Per-type keys: `select`→`options[]`/`multiple`/`min`/`max`; `media`→`mediaKind:'all'|'image'`/`multiple`/`min`/`max`; `reference`→`target` (collection **slug**, stable), `cardinality:'one'|'many'`, `onDelete:'restrict'|'setNull'|'cascade'`, `min`/`max`; `repeater`→`fields[]`/`min`/`max`; `number`→`mode:'int'|'decimal'`/`min`/`max`/`step`; scalars→optional `default`/`maxLength`.

### 2.2 `entry.data` JSON — the instance (on `collection_entries.data`)

References ride as id-lists **inside** the JSON (the editable projection) **and** are materialized as junction rows on save (the integrity source of truth). The JSON looks byte-identical to pure-JSON Candidate A; the difference is invisible in the column.

```jsonc
{
  "name":  "Ada Lovelace",
  "bio":   "<p>Countess of computing.</p>",   // sanitized HTML (server-side, beforeSave)
  "photo": 42,                                 // media row id
  "skills": [                                  // repeater: ordered list of sub-field maps
    { "label": "Analytical Engine", "level": "expert" },
    { "label": "Poetry",            "level": "intermediate" }
  ],
  "manager":  17,                              // reference cardinality one  → int|null
  "projects": [ 7, 19, 88 ]                    // reference cardinality many → int[]
}
```

Value shapes by type: text/textarea/rich_text → `string`; number → `int|float|null`; boolean → `bool`; date/datetime → ISO `string|null`; select single → `string|null`, multi → `string[]`; media single → `int|null`, multi → `int[]`; reference single → `int|null`, multi → `int[]`; repeater → `list<map>`. **Invariant: `data` is reference-by-id, never embedded.** Resolution is read-side.

**Orphaned-key tolerance (schema evolution):** `data` may contain keys no longer present in the schema (renamed/removed fields) and may lack keys newly added. `EntrySchemaValidator` **tolerates extra and missing keys without 500** — extra keys are preserved on read (not surfaced in the editor, not deleted on save) and missing keys default to empty/`null`. This is the read-side contract that makes destructive schema edits non-fatal to existing entries.

### 2.3 New columns / tables

**`collections` (Migration M-C1 + M-C7):**

| column | type | null | default | purpose | milestone |
|---|---|---|---|---|---|
| `kind` | string(20) | NO | `'multiple'` | singleton flag (`single`/`multiple`) | M-C5 |
| `max_depth` | tinyint unsigned | NO | `5` | per-collection reference-resolution cap | M-C4 |
| `is_public` | boolean | NO | `0` | entries get their own public URL | M-C6 |
| `view_template` | string(120) | YES | NULL | allowlisted public template element | M-C6 |

> `kind` is an enum-backed string (not `is_singleton` boolean) so a future `gallery`/`tree` kind costs nothing. `cardinality` from the consumption design is folded into `kind` — one column, one source of truth. `field_schema`/`data` columns are **never altered**; all field config rides inside the existing JSON.

**`collection_entry_references` (Migration M-C4) — the load-bearing junction:**

```php
$table = $this->table('collection_entry_references', ['id' => false, 'primary_key' => ['id']]);
$table
    ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
    ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => false])
    ->addColumn('source_entry_id', 'biginteger', ['signed' => false, 'null' => false])
    ->addColumn('field_name', 'string', ['limit' => 120, 'null' => false])
    ->addColumn('target_entry_id', 'biginteger', ['signed' => false, 'null' => false])
    ->addColumn('position', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
    ->addColumn('created', 'datetime', ['null' => false])
    ->addIndex(['source_entry_id', 'field_name', 'target_entry_id'], ['unique' => true, 'name' => 'uq_cer_edge'])
    ->addIndex(['target_entry_id'], ['name' => 'idx_cer_target'])              // reverse lookup
    ->addIndex(['source_entry_id', 'field_name', 'position'], ['name' => 'idx_cer_source'])
    ->addIndex(['workspace_id'], ['name' => 'idx_cer_workspace_id'])
    ->addForeignKey('workspace_id', 'workspaces', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_cer_workspace_id'])
    ->addForeignKey('source_entry_id', 'collection_entries', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_cer_source'])
    ->addForeignKey('target_entry_id', 'collection_entries', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION', 'constraint' => 'fk_cer_target'])
    ->create();
```

`source→CASCADE` (deleting the referencer drops its edges), `target→RESTRICT` (the belt: DB refuses to delete a referenced entry; `setNull`/`cascade` semantics are applied in the service before the DB delete — the FK is the backstop that turns a missed code path into a clean error, not a dangling edge). `workspace_id` carried explicitly so the reverse-lookup and integrity queries scope without a join (because `updateAll`/`deleteAll` bypass `TenantBehavior::beforeFind`).

**Optional, deferred:** `collection_entry_revisions` (entry history, mirrors `page_revisions`), `collection_entries.position` (manual entry ordering), `collection_entry_facets` (generated-column sort/filter at scale). All three are v2 (§8).

### 2.4 The singleton mechanism

`collections.kind = 'single'` makes the model first-class:
1. **Index** (`Admin/Collections/index.php`): a `single` collection links straight to *its one entry's* edit screen ("About settings ✎"), never to an entries list.
2. **`CollectionEntriesController::index`**: for `single`, redirects to `edit` of the sole entry, creating an empty draft on first visit (orchestrated by `SingletonEntryLocator`, not the controller). The draft is seeded with a **non-empty, collision-checked slug** (§2.5).
3. **Guard**: `CollectionEntriesTable::buildRules` rejects a 2nd entry when `kind === single` (and `multiple→single` with >1 existing entry → 422 via `CollectionKindConflictException`). "New entry" is hidden in templates **and** guarded server-side (never trust hidden UI).

### 2.5 Entry slug generation & uniqueness (foundational — referenced by §2.4, M-C2, M-C5, M-C6)

`collection_entries` carries `UNIQUE(collection_id, slug)`, and both the singleton and public-route designs dereference entries by slug. Slug generation is therefore a **first-class, specified mechanism**, not an implicit:

- `CollectionEntriesTable::beforeMarshal` fills `slug` from `title` via Cake's `Text::slug()` (the same dual-auto-slug helper shipped in `dfe40a2`) **only when the submitted slug is blank**. An author-supplied slug is respected verbatim (subject to the format rule below).
- **Collision suffixing** runs in `beforeSave` (after marshal, where the final slug is known and the row's own id is available on update): if `(collection_id, slug)` already exists for a *different* entry in this workspace, append `-2`, `-3`, … until unique. The uniqueness probe is a tenant-scoped finder (`findSlugTaken`) — never a raw query — and excludes the entry's own id on update so re-saving an unchanged entry is a no-op.
- **Format validation** in `validationDefault`: slug matches `^[a-z0-9]+(?:-[a-z0-9]+)*$`, max length bounded to the column. A malformed author slug is a 422, not a silent rewrite.
- **Singleton seeding** (M-C5 `SingletonEntryLocator`): the empty draft is created with a real title placeholder and a slug derived through the *same* `beforeMarshal`/`beforeSave` path — never an empty string, never a hardcoded `"entry"` that would collide across collections.

---

## 3. THE CHOSEN ARCHITECTURE & WHY

**Candidate C (Hybrid): JSON for leaves + repeaters, a single FK-backed `collection_entry_references` junction for reference edges.** The panel voted **C 3–0**. The decisive v1 requirement is *real reference edges* (single + many, with reverse-lookup and delete integrity — the worked `manager`/`projects` example). Pure JSON (A) cannot do this honestly: "what references entry 42?" is an unindexable `JSON_CONTAINS` full-table-scan per dynamic field path, and delete-integrity becomes a racy O(entries) app sweep. Full EAV (B) buys integrity but over-pays everywhere it shouldn't — it discards the working JSON model (fit=1), turns every schema edit into a transactional tenant-scoped migration, makes every editor read an EAV pivot, and encodes repeater rows as brittle `field_path.N.label` ordinal strings. C isolates integrity to the one place JSON can't serve it — a real, indexed, FK-backed edge — while text/rich-text/media/repeaters stay in the JSON model that already works, already sanitizes server-side via `BodySanitizer` on `beforeSave`, and already drives the `Collection::fields`/`CollectionEntry` property hooks and the existing editor. Judge 2 noted the reconciler is **not** novel risk: `BlockUsageIndexer` already does the identical "data-derived list → junction full-replace inside a transaction, explicit `workspace_id` scope" in this repo. Evolution stays lazy for leaves (cheap, same as A) and eager only for the bounded set of reference rows that must not dangle. The panel's standing risks — dual-source-of-truth drift, the `updateAll`/`deleteAll` tenant-scope trap, the RESTRICT-500 UX, the silent-429 rate-limit, cycle detection cost — are addressed in the cross-cutting checklist (§7) and pinned to specific milestone tests.

---

## 4. MILESTONES

Sequenced so the **broken schema builder is fixed first** (fastest user-visible win), then richer scalar fields + widgets, then repeater, then references (with integrity), then singleton, then consumption/rendering, then API. Each is PR-sized; all are independently shippable except M-C6 (requires C4+C5).

---

### M-C1 — Working schema builder + richer scalar field types

**Goal:** Replace the broken "can't add fields" form with an interactive drag/add/remove/reorder builder, add the scalar field types that need no new storage shape, and make destructive schema edits safe.

**Scope:**
- Fix the user-visible breakage: `schema-form.php` pads one row and its hint literally says rows "can be appended via the JS hook (M8d)" — a hook that was never written. Replace with a `[data-schema-builder]` mount in the `cms-editor-layout` shell + a new `schema-builder.mjs`.
- Extend `CollectionFieldType` with `RichText='rich_text'`, `DateTime='datetime'`, `Select='select'` (these three need **no** new column/table — additive to JSON). `Reference`/`Repeater` cases are **declared in the enum now** but their builder UI/widgets land in M-C3/M-C4.
- Single hidden `<input name="field_schema_json">`; JS owns the in-memory `fields[]` array, DOM is a pure projection. Reorder is **in-DOM renumber, no AJAX endpoint** (schema is one JSON column on one row — no per-field DB writes). Drag-zone math copied from `pages-index.mjs` (flat slice, no `guardNoCycles`).
- **Keyboard reorder (accessibility, real mechanism, not aspirational):** the copied `pages-index.mjs` drag math is pointer-only, so each field row ships explicit **"Move up / Move down" buttons** (and an `ArrowUp`/`ArrowDown`-while-focused handler on the drag handle) that perform the identical in-memory array swap + DOM renumber. Reorder is fully operable without a pointer.
- **Schema-evolution safety (live-data UX):** the builder reads a `data-fields-in-use` map (per-field count of entries with a non-empty value for that field name, supplied by `CollectionsTable::entryUsageByField`). Destructive edits — **type change, toggling `required` on, or deleting a field that has data** — trigger a `createModal` confirm interstitial naming the field and its in-use count ("`bio` has data in 12 entries; changing its type may break those values on next save — continue?"). Renames remain advisory (rename orphans the old `data` key; v1 does not migrate). Read-side behavior for orphaned keys is the §2.2 tolerance contract: orphaned keys are **preserved, not dropped**, and `EntrySchemaValidator` never 500s on extra/missing keys.
- Recursive `field_schema` validation on `CollectionsTable::validationDefault` (delegated to a `SchemaDefinitionValidator` service so the rule closure stays thin): name regex + `RESERVED` check, known type, per-level dup-name, `select` needs ≥1 option with unique values.
- One AJAX endpoint: `Collections::list` (reference-target dropdown — used by M-C4, but the endpoint + its GENEROUS entry land here to avoid a second pass).
- Auto-generate field `name` from `label` (underscore machine-key variant of the dual-auto-slug shipped in `dfe40a2`), freeze on manual edit (`data-name-touched`).
- **Full `CollectionPolicy` method set enumerated (permissions granularity):** authoring a *type* is a strictly higher bar than authoring an *entry*. The policy is specified in full here, not accreted ad hoc:
  - `canIndex` → any authenticated member (browse collections).
  - `canAdd` (create a new collection type) → **editorial role only**.
  - `canEditSchema` (alter `field_schema`) → **editorial role only**.
  - `canEdit` (rename/settings, non-schema) → editorial role only.
  - `canDelete` (delete a collection type) → **editorial role only**.
  - Entry-level authoring (`CollectionEntryPolicy`) — create/edit own entries — is the lower bar and lives on that policy (extended in M-C4/M-C5). Every controller action calls `$this->Authorization->authorize($entity, '<action>')`; no inline `if ($x->author_id !== …)`.

**New/changed files:**
- *migrations:* none (M-C1 adds no columns — scalar types are app-level).
- *src/Model:* `Enum/CollectionFieldType.php` (5 new cases + `label()` arms, `isStructured()`/`isMultiValueCapable()`/`configClass()`); `Entity/Collection.php` (`fields` hook builds typed rows incl. `options`, drops invalid-config rows fail-closed).
- *src/Service:* **new** `Service/Collection/SchemaDefinitionValidator.php`; **new** `Model/Collection/FieldDefinition.php` + `*FieldConfig.php` value objects (the shared descriptor; JS mirror in the centralized types module).
- *src/Controller:* `Admin/CollectionsController.php` (`parseForm` decodes `field_schema_json` + drops legacy `fields[]` path; new thin `list` action delegating to `CollectionsTable::findForReferencePicker`; `edit` emits `data-schema` + `data-fields-in-use`).
- *src/Model/Table:* `CollectionsTable.php` (`findForReferencePicker`, `entryUsageByField`, recursive validation via the validator).
- *src/Policy:* `CollectionPolicy.php` (full method set above: `canIndex`/`canAdd`/`canEditSchema`/`canEdit`/`canDelete`).
- *templates:* rewrite `templates/element/admin/collections/schema-form.php` (→ `cms-editor-layout` + mount + hidden inputs); `Admin/Collections/{add,edit}.php` load the module.
- *webroot/js:* **new** `webroot/js/admin/schema-builder.mjs` (imports `{ADMIN_BASE, csrfToken, getJson}` from `main.mjs`, `{createModal, el, showToast}` from `media-ui.mjs`; move-up/down + arrow-key reorder; destructive-edit confirm modal; does **not** load `page-editor.mjs`).
- *webroot/css:* `admin.css` — two new selectors only: `.cms-schema-group` (indented repeater card) + `.cms-schema-field__summary`.
- *src/Middleware:* `RateLimitMiddleware.php` GENEROUS += `['Collections','list']`.
- *tests:* `CollectionsControllerTest` (Prove-It — see below), `SchemaDefinitionValidatorTest`, `CollectionTest` (fields-hook round-trips new keys, drops unknown type fail-closed), `CollectionPolicyTest` (non-editorial member is denied `canAdd`/`canEditSchema`/`canDelete`).

**Prove-It sequencing (pinned):** the bug is that `parseForm()` reads `fields[]` and never `field_schema_json`. The two commits are ordered so the regression is visible:
1. **Commit 1 (red):** `CollectionsControllerTest::testSchemaBuilderAddsFieldRow` POSTs the **new** `field_schema_json` payload (two field rows) against **today's unmodified `parseForm`** and asserts the saved `field_schema` persists *both* rows. This fails because the legacy `parseForm` ignores `field_schema_json`. This commit lands **before** any `parseForm` change.
2. **Commit 2 (green):** rewrite `parseForm` to `json_decode($field_schema_json)` (dropping the legacy `fields[]` path); the test from commit 1 now passes.

**Data/migrations:** none.

**Demo/acceptance:** "You can now open a collection, click + Add field, pick a type (short text, long text, rich text, number, yes/no, date, date & time, choice list), reorder by dragging **or with Move-up/down / arrow keys**, remove a field, and save — and the schema persists with all rows. Changing a field's type or deleting a field that holds data prompts a 'N entries affected' confirmation first."

**Tests & quality gate:** Prove-It regression (sequenced above), then the fix. `/quality` green. PHPStan max forces the recursive `field_schema` shape to be typed — centralize the array-shape PHPDoc on `FieldDefinition`.

**Risks:** Validation-error re-hydration (builder must re-read `data-schema` from the patched-but-unsaved entity); rename-with-live-data warning is advisory only (rename orphans old `data` keys — v1 does not migrate, but never 500s on them).

**Dependencies:** none (the foundation).

---

### M-C2 — Rich-text + media widgets on the entry editor (+ entry slug + author_id hardening)

**Goal:** Make the new scalar field types actually authorable with real widgets, migrate the entry form to the polished editor shell, and close the two mass-assignment/slug correctness gaps the entry layer carries today.

**Scope:**
- Migrate `entry-form.php` from old `.cms-block-form` to `.cms-editor-layout` (main = dynamic widgets, sidebar = status/published_at/comments). Server renders initial widget HTML (works without JS); the module enhances.
- **Rich-text:** `rich_text` field → `<textarea data-collections-tiptap name="data[<name>]">` mounted by a **new `collections-editor.mjs`** that imports the same `vendor/tiptap.bundle.mjs` + reuses page-editor's editor-construction/toolbar block (lines ~243–395) but **not** its page autosave/sidebar/block-picker bindings. `BlockReference` extension excluded (reusable blocks are page/post-only). On submit, `textarea.value = editor.getHTML()`.
- **Server-side sanitization at the save boundary:** `CollectionEntriesTable::beforeSave` on `isDirty('data')` runs `BodySanitizer::clean()` on every `rich_text` value (a `Service/Collection/EntryDataSanitizer` walks `collection->fields`, recursing into repeater rows later). The choice of `beforeSave` (not `beforeMarshal`) is to cover **direct property assignment generally** — i.e. any code path that sets `$entry->data` outside the marshaller, including the future entry-revision restore should §8 ever build it. (See the revision decision below — the rationale is stated as "covers direct assignment," **not** as "covers an existing restore path," because no entry-restore path exists today.) Reuse `BodySanitizer` as-is; if collection rich-text should **exclude** `div[data-block]`, add a **parameterized** allowlist (the current `BodySanitizer::clean(string)` is a single fixed allowlist — parameterizing it is **net-new work**: constructor-arg or sibling sanitizer, never mutate `ALLOWED` in place) and bump `HTML.DefinitionRev` (currently **3**). Recommend excluding `data-block` from entry rich-text in v1.
- **Entry-revision decision (resolves the under-argued deferral):** entry revision history is **explicitly deferred to v2** (§8) — Pages/Posts have `page_revisions`/`RevisionRestorer`; entries get none in v1. Because the restore path does not exist, the `beforeSave` rationale above is written as "covers direct assignment generally," removing the dangling citation to a non-existent code path. If a future milestone builds entry revisions, the `beforeSave` choice already covers the restore assignment with no change.
- **Mass-assignment hardening (BLOCKER fix, lands here in the entry-editor pass — not deferred to §7):** `CollectionEntry::$_accessible` today has `author_id => true`, which lets an author **forge authorship via PATCH**. Flip **`author_id => false`** — the controller/Tenant stamps it from the identity, never the request body. `slug => true` is **retained intentionally** (authors may set a custom slug) now that §2.5 validates its format and dedups collisions server-side; `data => true` is retained (the marshaller, not raw `patchEntity`, builds `data`). `workspace_id` stays non-accessible (Tenant stamps it). No `'*' => true`.
- **Media:** replace the raw `<input placeholder="media UUID or URL">` with a "Choose media" button → `openMediaPicker({kind})` writing `picked.id` into a hidden `data[<name>]` input + thumbnail preview; multi → drag-reorderable thumb strip (in-DOM renumber). Zero new endpoint (`media/library` already GENEROUS).
- **Per-field validation error round-trip (flat fields):** a 422 returns dotted error paths (`bio`, `manager`). Specify the binding: every widget container carries `data-field-path="<name>"`; on a failed save the editor re-hydrates from the **patched-but-unsaved** entity (`data-schema` + posted `data`) and renders each error into the matching widget's `cms-field__error` by `data-field-path` lookup. No error is silently dropped; no widget loses the author's unsaved input. (Repeater dotted paths extend this in M-C3.)
- Move the `coerce()` match-ladder out of the controller into the validator path (the controller `coerce()` has no case for the structured types and doesn't scale).

**New/changed files:**
- *migrations:* none.
- *src/Service:* **new** `Service/Collection/EntryDataSanitizer.php`; (net-new) parameterized allowlist on `Service/Page/BodySanitizer.php` (constructor arg / sibling — do not mutate `ALLOWED`; bump `DefinitionRev` if the branch ships).
- *src/Model/Entity:* `CollectionEntry.php` (`author_id => false`; `slug` stays true).
- *src/Model/Table:* `CollectionEntriesTable.php` (`beforeMarshal` slug-fill, `beforeSave` collision-suffix + `findSlugTaken`, `beforeSave` sanitize hook; slug format in `validationDefault`).
- *src/Controller:* `Admin/CollectionEntriesController.php` (`parseForm` shrinks to core fields + raw `data` passthrough for new widgets; stamps `author_id` from identity).
- *templates:* rewrite `entry-form.php` (editor-layout shell, per-type widget partials emitting `data[...]` name paths, `data-field-path` on each); small per-widget partials.
- *webroot/js:* **new** `webroot/js/admin/collections-editor.mjs` (widget factory + error re-hydration by `data-field-path`).
- *tests:* `EntryDataSanitizerTest` (`<script>` stripped per rich_text field, non-rich untouched, empty→`''`); `CollectionEntriesTableTest` (slug auto-fill from title, collision suffix `-2`, custom slug respected, malformed slug → 422, re-save no-op); **Prove-It `CollectionEntryTest::testAuthorIdNotMassAssignable`** (PATCH with a forged `author_id` does not change the stored author); integration test that a posted rich_text body is sanitized in the stored blob.

**Data/migrations:** none.

**Demo/acceptance:** "You can now write a rich-text field with the same toolbar as Pages, pick an image with the media picker, leave the slug blank to auto-derive it from the title (collisions auto-suffix), and a pasted `<script>` is stripped server-side on save. Forging `author_id` via PATCH no longer changes the author, and a 422 re-renders each field's error against the right widget without losing your input."

**Tests & quality gate:** Prove-It for sanitization (`<script>` round-trip) and for `author_id` forgery. `/quality` green. Bump `DefinitionRev` if the allowlist branches.

**Risks:** Sanitizer allowlist divergence — decide the `data-block` policy **before** shipping (post-launch change needs a `DefinitionRev` bump + cache flush). The PHP widget partials and JS `buildWidget` must emit byte-identical name paths and `data-field-path` values or error re-hydration mis-targets.

**Dependencies:** M-C1 (the new field types must exist).

---

### M-C3 — Repeater field type (inline multifield, one level)

**Goal:** A repeatable group of heterogeneous sub-fields (AEM multifield), depth-1, both in the builder and the entry editor, with nested error round-trip.

**Scope:**
- Builder: `repeater` row renders an indented `.cms-schema-group` card with "+ Add sub-field"; the sub-field type chooser **excludes `repeater` and `reference`** (the 1-level cap, enforced client-side AND re-enforced in `CollectionsTable` validation AND in the entry renderer). `renderFieldRow(field, depth)` is the recursive pure function. Sub-fields get the same move-up/down keyboard reorder as top-level.
- Entry editor: `repeaterWidget` renders row cards from `data[<name>][]`; "Add row" clones a `<template>` substituting `__ROW__`→index, "Remove row", drag-reorder (in-DOM renumber rewrites every `[i]`). `min`/`max` enforced client + server. Sub-widgets reuse the **same** `buildWidget(field, namePath, value)` factory recursively (so rich-text inside a row gets its own `Editor`, mounted on row insert).
- **Recursive marshalling must land atomically:** the `EntryDataSanitizer` recursion into repeater rich_text rows, the `fields`-hook recursion, validation recursion, and the nested UI ship in **one slice** — a half-recursive marshaller corrupts nested data.
- `EntrySchemaValidator` (the deep schema-aware check in `beforeSave`): repeater value is `list`, `min`/`max` rows, recurse per row with dotted error paths (`skills.2.label`). Per-level dup-name now recursive. Honors the §2.2 tolerance contract: extra/missing keys never 500.
- **Per-field error round-trip (nested):** extend the M-C2 binding to dotted nested paths. A row widget carries `data-field-path="skills.2.label"`; on a 422 the editor re-renders unsaved row state from the patched entity and maps each dotted error back to its row widget by exact `data-field-path` match. Re-index logic preserves the author's row order while binding errors to the *pre-reindex* positions the validator reported.

**New/changed files:**
- *migrations:* none (repeater is `field_schema.fields` + nested `data` — JSON only).
- *src/Service:* **new** `Service/Collection/EntrySchemaValidator.php` (presence, coercion, enum membership, repeater min/max + one recursive pass; tolerant of orphaned keys); extend `EntryDataSanitizer` to recurse.
- *src/Model:* `Entity/Collection.php` (`fields` hook recurses into `repeater.fields`); `Enum/CollectionFieldType.php` (`Repeater` builder/widget wired).
- *src/Model/Table:* `CollectionsTable.php` (validation rejects nested repeater/reference; recursive dup-name); `CollectionEntriesTable.php` (`beforeSave` runs `EntrySchemaValidator`, sets dotted errors, aborts on failure).
- *src/Controller:* `Admin/CollectionEntriesController.php` (`parseForm` recursive coercion for repeater rows; re-index gaps to contiguous 0-based).
- *templates:* `entry-form.php` repeater container; **new** `templates/element/admin/collections/repeater-row.php` + `<template data-repeater-row>`; per-row dotted-path error display bound by `data-field-path`.
- *webroot/js:* extend `schema-builder.mjs` (sub-field group + keyboard reorder) + `collections-editor.mjs` (`repeaterWidget`, `addRow`, `reindexNames`, `bindRowDrag`, nested error re-hydration).
- *src/Exception:* **new** `Exception/Collection/SchemaValidationException.php`.
- *tests:* `EntrySchemaValidatorTest` (repeater min/max, recursion, dotted paths, nested rich_text sanitized, orphaned-key tolerance), integration POST with a 2-item repeater persists nested `data`, and a 422 maps `skills.1.label` back to the right row widget.

**Data/migrations:** none.

**Demo/acceptance:** "You can now define a 'Skills' repeater with sub-fields (skill name + a choice-list level), add/remove/reorder rows in an entry, each row's rich-text is sanitized, and a validation error on row 2's skill name lands on exactly that row's field after a failed save."

**Tests & quality gate:** Prove-It for nesting-depth cap (a repeater-in-repeater is rejected at builder save) and repeater min/max. `/quality` green.

**Risks:** Half-recursive marshaller corrupting data — the slice is atomic by design. The widget factory must be pure/name-path-driven (not hardcoded to top-level) or sub-row rich-text won't mount and nested errors won't bind.

**Dependencies:** M-C1 (builder), M-C2 (widget factory + sanitizer + flat error round-trip).

---

### M-C4 — References with integrity (the headline feature)

**Goal:** An entry references one-or-many entries of another collection, with DB-backed integrity, indexed reverse lookup, a cycle-safe depth-capped resolver, a real picker, and author-facing surfaces for "used in" and pre-delete impact.

**Scope:**
- **Migration M-C4a:** `collection_entry_references` junction (§2.3) — must deploy **before** any reference field is saveable. **Migration M-C4b:** `collections.max_depth` column (default 5).
- `CollectionEntryReferencesTable` (reached via `getAssociation('CollectionEntryReferences')`, never magic property). `CollectionEntriesTable` gains the association + a `findReferencingSources($entryId)` finder.
- **`ReferenceReconciler`** (`beforeSave`/in the marshaller's transaction): diff the `data` id-lists against existing junction rows for `(source, field_name)`, tenant-scoped, insert/delete the delta with `position`. The proven `BlockUsageIndexer` full-replace pattern. Junction is **authoritative for integrity**; JSON is the editable cache.
- **`ReferenceIntegrityService`** (delete-time): apply per-field `onDelete` for inbound edges — `restrict` → block with `ReferencedEntryInUseException` (controller → 409); `setNull` → rewrite source `data` + delete edge; `cascade` → delete sources. All in one transaction, workspace-scoped via `TenantContext::instance()->requireWorkspaceId()` (because `updateAll`/`deleteAll` bypass `beforeFind` — the highest-severity correctness trap). Pre-check via `findReferencingSources` for a friendly message before the DB RESTRICT fires; catch the integrity exception in `delete` so RESTRICT never 500s.
- **Reference-integrity UX (what the author SEES, not just HTTP codes):**
  - **Pre-delete impact interstitial:** the entry `delete` action, before committing, renders a confirmation screen listing the referencing entries by **title + edit link** (from `findReferencingSources`) with a clear consequence per field policy ("3 entries reference this; deleting will: block / null these references / cascade-delete them"). For `restrict`, deletion is refused with that list and a flash; for `setNull`/`cascade` the author confirms the named consequence.
  - **"Used in" panel:** the entry editor sidebar shows a read-only **"Referenced by"** list (reverse lookup via `idx_cer_target` + `findReferencingSources`) — entry titles linking to their edit screens. Cheap, since the finder already lands here.
- Recursive-CTE **cycle check at attach time** over the junction (structural guard) — not relying on the resolver cap alone, or cycles persist in storage and fail only at render. `ReferenceCycleException`.
- **`EntryReferenceResolver`** (read-side): inline expansion, **`MAX_DEPTH = 5`** + path-scoped visited-set cycle guard, **batched** (`resolveMany`: one query per target collection per level, not per entry — the N+1 killer, mirrors `BlockExpander::loadBlocks`). Returns `ResolvedEntry`/`ResolvedField` readonly DTOs. Used by M-C6.
- **Reference picker widget** (entry editor): clone the tag-combobox (`page-editor.mjs` ~641–785) → debounced `GET collections/{id}/entries/search?q=` → pills (entry title) backed by hidden `data[<name>][]` id inputs; single replaces the one pill, many drag-reorders. `excludeEntryId` = the entry being edited (cheap self-reference guard). `resolve?ids=` hydrates pill titles on load.
- Builder: `reference` config modal — target collection `<select>` (from `Collections::list`), cardinality radio, optional min/max, `onDelete` policy. Schema validation: `target` resolves to a collection **in this workspace**.
- Reconcile **on schema edit**: if a reference field is removed or its `target` changes, eagerly `deleteAll` the now-invalid edges (the only eager path, bounded to reference rows).
- One-time idempotent backfill command `bin/cake collections_rebuild_references` (no-op on legacy data; safety net for the deploy window).
- `CollectionEntryPolicy` extended: `canAttachReference`/`canDetachReference` (→ `canEdit` semantics), and `canPublish` (editorial-only) — the latter wired to the actual `publish` `authorize()` call in `CollectionEntriesController` (confirmed present, now gated).

**New/changed files:**
- *migrations:* **new** `config/Migrations/*_CreateCollectionEntryReferences.php`, `*_AddMaxDepthToCollections.php`.
- *src/Model/Table:* **new** `CollectionEntryReferencesTable.php`; `CollectionEntriesTable.php` (association, `findSearchInCollection` via `QueryExpression::like` — never string-interpolated; `findReferencingSources`; `buildRules` reference targets `existsIn` same workspace; `afterSave` edge projection).
- *src/Service:* **new** `Service/Collection/ReferenceReconciler.php`, `ReferenceIntegrityService.php`, `EntryReferenceResolver.php`; **new** DTOs `ResolvedEntry.php`/`ResolvedField.php`/`ResolvedRef.php`/`ResolvedMedia.php`.
- *src/Exception:* **new** `Exception/Collection/{ReferenceCycleException, ReferencedEntryInUseException, CrossWorkspaceReferenceException, ReferenceDepthExceededException}.php`.
- *src/Controller:* `Admin/CollectionEntriesController.php` (`search`, `resolve` AJAX actions, thin; `delete` renders the pre-delete impact interstitial and catches the integrity exception → 409; `edit` passes the "referenced by" list to the sidebar); `Admin/CollectionsController.php` (schema-edit edge cleanup).
- *src/Command:* **new** `Command/CollectionsRebuildReferencesCommand.php`.
- *src/Policy:* `CollectionEntryPolicy.php` (`canAttachReference`/`canDetachReference` → `canEdit`; `canPublish` editorial-only; `referenceSearch` authorizes the **source** entry's edit right).
- *templates:* `entry-form.php` reference pills + picker + "Referenced by" sidebar list; **new** `templates/Admin/CollectionEntries/delete_confirm.php` (impact interstitial); `schema-builder.mjs` reference config modal.
- *webroot/js:* `collections-editor.mjs` (`referenceWidget`, `openReferencePicker`, `referencePill`, `hydrateReferenceTitles`).
- *src/Middleware:* GENEROUS += `['CollectionEntries','search']`, `['CollectionEntries','resolve']`.
- *tests:* cross-workspace reference id rejected; wrong-target-collection rejected; dangling id rejected; reverse-lookup indexed + surfaced in the "Used in" panel; **`A→B→A` cycle terminates** (CTE + resolver); **`MAX_DEPTH` hop returns stub + `truncated`**; **N entries → 2 queries not N+1** (assert via query log); delete-of-referenced → 409 not 500 **and** the impact interstitial lists the referencing entries; **tenant-scope test** that a delete in workspace A never rewrites B's edges; `collections_rebuild_references` idempotent; `canPublish` denies a non-editorial member.

**Data/migrations:** M-C4a/M-C4b (above); backfill command run once post-deploy.

**Demo/acceptance:** "You can now add a 'Manager' (one) and 'Projects' (many) reference field, search-and-pick target entries as pills, see a 'Referenced by' list in the sidebar, and on delete get an impact screen listing who references this entry — the system blocks the delete (or nulls/cascades per policy) and a reference cycle can't be saved."

**Tests & quality gate:** Full panel risk-test set above. `/quality` green; PHPStan max types the resolver's recursive shapes.

**Risks (panel-flagged, all tested):** dual-source-of-truth drift (reconcile on every `isDirty('data')`; junction authoritative); tenant-scope footgun on `updateAll`/`deleteAll` (explicit `requireWorkspaceId()`); FK RESTRICT-500 (catch + translate + interstitial); silent 429 (GENEROUS); cycle persisting in storage (CTE at attach, not resolver-only); slug-retarget pointing edges at the wrong collection (eager cleanup on `target` change).

**Dependencies:** M-C1 (`Reference` enum case + `Collections::list`), M-C2 (widget factory). Resolver consumed by M-C6.

---

### M-C5 — Singleton mode (+ admin entry-list search)

**Goal:** A collection can be a single edit-in-place entry (Site Settings) or multiple entries; and authors of large collections can find an entry without scrolling.

**Scope:**
- **Migration M-C5:** `collections.kind` (string, default `'multiple'`); `CollectionKind` enum; `Collection::kind` property hook; add `kind` to `$_accessible`.
- Builder sidebar: singleton toggle ("Single entry / Multiple entries") writing `kind`.
- **`SingletonEntryLocator`** service: `edit(Collection, authorId)` returns the sole entry or creates an empty draft (seeded title placeholder + a real collision-checked slug per §2.5, status=draft, data=`[]`; Tenant stamps `workspace_id`; `author_id` stamped from identity, never the body).
- `CollectionEntriesController::index` redirects `single` collections to edit-in-place; `add` guard rejects a 2nd entry (`SingletonEntryExistsException` → `ConflictException`). `CollectionEntriesTable::buildRules` enforces ≤1 entry when `single`; `multiple→single` with >1 entry → 422 (`CollectionKindConflictException`). The singleton redirect is a normal **full-page** navigation (`index`/`edit` are not AJAX), so no GENEROUS entry is needed for it — stated explicitly so the rate-limit enumeration stays complete.
- `Admin/Collections/index.php`: singleton row links to its entry's edit, not a list; "Add" hidden.
- **Admin entry-list search (v1, distinct from deferred at-scale facets):** `CollectionEntries::index` (admin) gains a basic **title/slug `LIKE` filter** (`findAdminSearch` via `QueryExpression::like`, never interpolated) + pagination, so a collection with 200 entries is navigable. This is a simple indexed-column search, explicitly **not** the deferred custom-field/`collection_entry_facets` indexing (§8).

**New/changed files:**
- *migrations:* **new** `*_AddKindToCollections.php`.
- *src/Model:* **new** `Enum/CollectionKind.php`; `Entity/Collection.php` (`kind` hook + `$_accessible`).
- *src/Service:* **new** `Service/Collection/SingletonEntryLocator.php`.
- *src/Model/Table:* `CollectionEntriesTable.php` (singleton `buildRules`, `findAdminSearch`); `CollectionsTable.php` (`multiple→single` conflict rule).
- *src/Controller:* `Admin/CollectionEntriesController.php` (`index` singleton redirect + title/slug search filter, `add` guard).
- *src/Exception:* **new** `Exception/Collection/{SingletonEntryExistsException, CollectionKindConflictException}.php`.
- *templates:* `Admin/Collections/index.php`, `Admin/CollectionEntries/index.php` (search box), `schema-form.php` (toggle).
- *tests:* singleton add-redirects-to-edit-in-place; 2nd entry rejected; `multiple→single` with >1 entry → 422; singleton draft seeds a non-empty unique slug; admin search filters by title/slug.

**Data/migrations:** M-C5 (defaulted column, no backfill).

**Demo/acceptance:** "You can now mark a collection 'Single entry' (e.g. About settings); its index opens straight into one editable record, creating it (with a real slug) on first visit, and a second entry is refused. For multi-entry collections, a title/slug search box keeps a 200-entry list navigable."

**Tests & quality gate:** integration flow tests above. `/quality` green.

**Risks:** the `add`/index guards must be server-side (hidden UI is not the boundary).

**Dependencies:** M-C1.

---

### M-C6 — Public consumption & rendering

**Goal:** Entries are viewable at a public URL, embeddable in Page/Post bodies via the block rail, and readable from templates as singletons — all through the one resolver. **Requires M-C4 + M-C5.**

**Scope:**
- **Migration M-C6:** `collections.is_public` (bool, default 0) + `collections.view_template` (string, allowlisted).
- **Public routes** (before the Pages catch-all `/*`, inside `/{workspaceSlug}`): `/c/{collectionSlug}/{entrySlug}` → `CollectionEntries::view`, `/c/{collectionSlug}` → `::listing`. `/c` joins `RESERVED_SLUGS`. The `/c/` namespace avoids shadowing two-level Page paths.
- **Public `CollectionEntriesController`** (no Admin prefix, thin, mirrors `PostsController::view`): `find('publicBySlug')` + `find('livePublic')` (the publication gate — `status=live AND (published_at IS NULL OR <= now)`), then `EntryReferenceResolver::resolve` at `MAX_DEPTH`, render via an **allowlisted** `CollectionTemplate` enum (never raw `setTemplate($view_template)` — path-traversal vector). A `generic` element renders field-by-field off the schema so a new collection is viewable the moment `is_public` flips. `listing` paginates `find('livePublic')`, resolving at shallow depth (refs as `ResolvedRef` stubs).
- **Public escaping:** scalars `h()`'d; `rich_text` echoed **raw** (sanitized at save, like `$this->Block->expand($body)`). Resolver does **not** re-sanitize on read.
- **Embedding:** new `BlockType::CollectionEntry` case whose `data` carries `{collection_id, entry_id, variant}`. Rides the existing `data-block` rail end-to-end (block picker → `BlockExpander::render` new `match` arm → `CollectionEmbedRenderer`). Batched via `loadEmbeddedEntries(blocks)` so a page embedding 6 members is one entries query + one resolve batch. Honors the BlockExpander "load up-front, render from id-keyed maps, never query in a render arm" invariant.
- **Media/asset batching in the resolver (close the repeater-N+1 gap):** `EntryReferenceResolver` (or its consumer) batch-loads **media rows alongside entry refs** — a 20-row repeater each carrying a media id resolves with **one media query**, not one per row. The batch analysis covers media, not only entry references. Asserted by a query-count test.
- **Page-cache / embed-invalidation note:** the repo currently has **no page-output cache** for rendered Page/Post bodies (render is request-time via `BlockExpander`), so flipping an embedded entry is immediately reflected and there is **no stale-embed invalidation gap — N/A in v1**. If a page cache is introduced later, embedded-entry edits must bump the cache key for pages that embed them; this is recorded here so the constraint travels with any future caching work.
- **Singleton template seam:** `CollectionHelper::singleton($slug)` / `entry($slug,$entrySlug)` mirrors `BlockHelper::expand`, backed by a per-request-memoized `CollectionConsumer`. Layouts read `$this->Collection->singleton('site-contact')`.

**New/changed files:**
- *migrations:* **new** `*_AddCollectionDeliveryColumns.php`.
- *src/Model:* **new** `Enum/CollectionTemplate.php` (allowlist + `element()`); `Enum/BlockType.php` (+`CollectionEntry`).
- *src/Service:* **new** `Service/Collection/CollectionConsumer.php`, `CollectionEmbedRenderer.php`; `Service/Page/BlockExpander.php` (new render arm + `loadEmbeddedEntries`); `EntryReferenceResolver` media-batch hydration.
- *src/Model/Table:* `CollectionsTable.php` (`findPublicBySlug`); `CollectionEntriesTable.php` (`findLivePublic`).
- *src/Controller:* **new** public `Controller/CollectionEntriesController.php` (`view`/`listing`, thin).
- *src/View/Helper:* **new** `View/Helper/CollectionHelper.php`.
- *templates:* **new** `templates/CollectionEntries/{view,listing}.php` + `templates/element/collections/public/{generic,team-member,faq,…}.php`.
- *config:* `routes.php` (`/c/...` before catch-all; `/c` → `RESERVED_SLUGS`).
- *src/Middleware:* GENEROUS += `['CollectionEntries','view']`, `['CollectionEntries','listing']`.
- *tests:* resolver cycle-guard/depth-cap/batch-count (from M-C4, exercised end-to-end); **repeater-of-media resolves in one media query** (the new batch assertion); `/c/{cs}/{es}` 404s a draft entry like `/blog/{slug}`; a draft *referenced* entry resolves to a dropped/stub ref (no draft leak); embed batched (6 entries = 1 query + 1 resolve).

**Data/migrations:** M-C6 (defaulted columns).

**Demo/acceptance:** "You can now flip a collection public, browse its entries at `/c/team/ada-lovelace`, embed a team member inside a page body, and read a Site Settings singleton from a layout — drafts 404, references resolve inline up to 5 levels, and a 20-row repeater of photos loads in a single media query."

**Tests & quality gate:** resolver + public-gate + embed-batch + media-batch tests. `/quality` green.

**Risks:** route ordering (must precede `/*`); `view_template` allowlist (never raw template path); listing depth policy (stubs, not full expansion, or list views explode).

**Dependencies:** **M-C4 (resolver + DTOs) AND M-C5 (singleton helper seam)**, plus M-C2 (sanitized rich-text trusted-raw on read).

---

### M-C7 — Read-only JSON API (AEM Content Services analog)

**Goal:** A headless JSON delivery surface reusing the resolver verbatim. GraphQL deferred (§8).

**Scope:**
- **Public routes** (GET only, before catch-all): `/api/collections/{collectionSlug}` → `apiList`, `/api/collections/{collectionSlug}/{entrySlug}` → `apiView`.
- `apiList` → paginated `find('livePublic')`, each item resolved at a shallow ceiling (refs as stubs); `?expand=1` opts into full depth. `apiView` → one entry at `MAX_DEPTH`, serialized via `ResolvedEntry::jsonSerialize()` (flattens `fields` to `{name: value}` + `_path`-equivalent metadata). Both via CakePHP `JsonView` (`_serialize`) — no hand-rolled `json_encode`.
- Same `ResolvedEntry` the templates + embeds consume — one resolver, one shape, four surfaces.

**New/changed files:**
- *migrations:* none.
- *src/Controller:* public `CollectionEntriesController.php` (+`apiList`/`apiView`, thin; `JsonView`).
- *src/Service:* `ResolvedEntry::jsonSerialize()` (lands here if not already in M-C4).
- *config:* `routes.php` (`/api/collections/...` before catch-all, GET-only).
- *src/Middleware:* GENEROUS += `['CollectionEntries','apiList']`, `['CollectionEntries','apiView']`.
- *tests:* `apiView` returns the resolved shape + metadata; `apiList` paginates with stub refs; `?expand=1` deepens; draft 404; referenced-draft stubs (no leak).

**Data/migrations:** none.

**Demo/acceptance:** "You can now `GET /{ws}/api/collections/team/ada-lovelace` and get the resolved entry as JSON, or `GET /{ws}/api/collections/team` for a paginated list."

**Tests & quality gate:** API shape + pagination + gate tests. `/quality` green.

**Risks:** the same publication-gate leak risk (referenced drafts must stub); list responses must stay shallow by default or N+1 reappears.

**Dependencies:** M-C6 (resolver + public controller + route precedence).

---

## 5. DEPENDENCY GRAPH

```
M-C1 (schema builder + scalar types + full policy + keyboard reorder + evolution guard)   ← foundation, no deps
  ├─► M-C2 (rich-text + media widgets + slug gen + author_id fix + flat error round-trip)
  │     └─► M-C3 (repeater + nested error round-trip)   [needs widget factory + sanitizer]
  ├─► M-C4 (references + integrity + "used in" + delete impact)  [needs Reference enum + Collections::list from C1, widget factory from C2]
  │     └─► M-C6 (consumption/rendering + media batch)  [needs resolver + DTOs (C4) AND singleton helper (C5)]
  │           └─► M-C7 (JSON API)                        [needs resolver + public controller + routes]
  └─► M-C5 (singleton + admin entry search)             [needs C1; feeds the helper seam in C6]
                                                          M-C6 also depends on M-C5 (singleton helper)

Ship order:  M-C1 → M-C2 → M-C3 → M-C4 → M-C5 → M-C6 → M-C7
(M-C3 and M-C4 both depend only on C1/C2 and are mutually independent — parallelizable with two people;
 M-C5 depends only on C1 and can slot anywhere after C1, but must precede C6.
 Every milestone is PR-sized; all are independently shippable EXCEPT M-C6, which requires C4+C5.)
```

---

## 6. FIRST SLICE

**Do M-C1 first.** It fixes the headline user-visible breakage ("you cannot add fields" — the hint literally says the JS hook was never written), delivers richer scalar field types and the keyboard-operable builder, defines the full collection-type permission set, and adds zero migrations. Exact first steps:

1. **Write the Prove-It failing test (commit 1, red, before any `parseForm` change):** `CollectionsControllerTest::testSchemaBuilderAddsFieldRow` — POST `Admin/Collections::edit` with `field_schema_json` containing two field rows against **today's unmodified controller**, assert the saved `field_schema` persists both. This **fails on today's code** because `parseForm` only reads `fields[]`. Run it, confirm red, commit the test alone.
2. **Extend `src/Model/Enum/CollectionFieldType.php`** — add `RichText='rich_text'`, `DateTime='datetime'`, `Select='select'`, plus `Reference`/`Repeater` cases (declared now, UI later), with `label()` arms (plain-English: "Rich text", "Date & time", "Choice list", "Linked entry", "Repeatable group") and `isStructured()`/`isMultiValueCapable()`/`configClass()`.
3. **Create the shared descriptor** `src/Model/Collection/FieldDefinition.php` + `*FieldConfig.php` value objects and generalize the `Collection::fields` property hook to build them (fail-closed on invalid config). Add the `SchemaDefinitionValidator` service and wire `CollectionsTable::validationDefault` to it (recursive shape: name regex + `RESERVED`, per-level dup, select ≥1 option). Add `CollectionsTable::entryUsageByField` for the evolution guard.
4. **Define the full `CollectionPolicy`** (`canIndex`/`canAdd`/`canEditSchema`/`canEdit`/`canDelete`, with `canAdd`/`canEditSchema`/`canDelete` editorial-only) and its `CollectionPolicyTest`.
5. **Rewrite `CollectionsController::parseForm` (commit 2, green)** to `json_decode($field_schema_json)` (drop the legacy `fields[]` path), and rewrite `templates/element/admin/collections/schema-form.php` into the `cms-editor-layout` shell with the `[data-schema-builder]` mount + hidden `field_schema_json` + emitted `data-schema`/`data-fields-in-use`. Run the Prove-It test — confirm green.
6. **Write `webroot/js/admin/schema-builder.mjs`** (imports from `main.mjs`/`media-ui.mjs`; add/remove/reorder via in-DOM array mutation + `pages-index.mjs` drag-zone math; **Move-up/down buttons + ArrowUp/Down handler** for pointer-free reorder; destructive-edit confirm modal driven by `data-fields-in-use`; auto-name from label; serialize to the hidden input on submit). Add `['Collections','list']` to GENEROUS, then run `/quality`.

---

## 7. CROSS-CUTTING CHECKLIST

- **Tenancy:** `CollectionsTable`/`CollectionEntriesTable` keep `TenantBehavior` (scopes `beforeFind`). **Every** `updateAll`/`deleteAll`/raw query — the reference reconciler, delete-time integrity, schema-edit edge cleanup, the recursive-CTE cycle check, the slug-collision probe — **must** inject `workspace_id` explicitly via `TenantContext::instance()->requireWorkspaceId()` (these bypass `beforeFind`). The junction's explicit `workspace_id` column exists for this. Reference *targets* and media ids are validated to belong to the current workspace — a valid id in another workspace is a validation failure, not a success. Dedicated cross-tenant test on the delete path.
- **`RateLimitMiddleware::GENEROUS` additions** (BLOCKER — the most-forgotten step; default tier is 1 rps/burst 10 and silently 429s bursty XHR; GENEROUS currently holds 16 pairs, none for Collections): `['Collections','list']` (M-C1); `['CollectionEntries','search']`, `['CollectionEntries','resolve']` (M-C4); `['CollectionEntries','view']`, `['CollectionEntries','listing']` (M-C6); `['CollectionEntries','apiList']`, `['CollectionEntries','apiView']` (M-C7). **Seven additions total.** Schema reorder is **in-form, no endpoint**; the singleton create-on-first-visit redirect is a **full-page** navigation, not XHR — neither needs a GENEROUS entry. Keep the throttle test that fires a deliberately-omitted action to prove the guard catches a missing entry.
- **Server-side sanitization:** rich-text is sanitized at the **save boundary** in `CollectionEntriesTable::beforeSave` on `isDirty('data')` (not `beforeMarshal` — to cover **direct property assignment generally**, the rationale stated without citing a non-existent entry-restore path), reusing `BodySanitizer`, recursing into repeater rows. The client editor schema is **never** the boundary (an author can PATCH arbitrary HTML to autosave). Excluding `data-block` requires **parameterizing** `BodySanitizer` (net-new — its `clean(string)` is a single fixed allowlist today; constructor-arg/sibling, never mutate `ALLOWED`) and bumping `HTML.DefinitionRev` (currently **3**).
- **Policies (full set):** `CollectionPolicy` = `canIndex` (member) / `canAdd`,`canEditSchema`,`canEdit`,`canDelete` (editorial-only — creating a *type* is a higher bar than authoring entries). `CollectionEntryPolicy` adds `canAttachReference`/`canDetachReference` (→ `canEdit`), `canPublish` (editorial-only, wired to the `publish` authorize call). Every action calls `$this->Authorization->authorize($entity, '<action>')`; no inline `if ($x->author_id !== …)`.
- **Mass-assignment:** confirmed current `CollectionEntry::$_accessible` has `data`, `slug`, **and `author_id`** all `true`. **Fix in M-C2:** `author_id => false` (controller/Tenant stamps from identity — forging authorship via PATCH is the BLOCKER). `slug => true` retained (custom slugs allowed; format-validated + collision-suffixed per §2.5). `data => true` retained (marshaller builds it). `Collection::$_accessible` += `kind`,`max_depth`,`is_public`,`view_template`; `field_schema` stays accessible (config rides inside, marshalled through recursive validation). `workspace_id` non-accessible. **No `'*' => true`** anywhere (review blocker).
- **Slug generation (foundational):** every entry has a non-empty, `(collection_id, slug)`-unique slug via `beforeMarshal` auto-fill + `beforeSave` collision suffix + `validationDefault` format rule (§2.5). Singleton drafts seed through the same path — never an empty or hardcoded slug.
- **Schema evolution / orphaned keys:** `EntrySchemaValidator` **tolerates extra and missing `data` keys without 500**; orphaned keys are preserved (not dropped). The builder surfaces `data-fields-in-use` counts and confirms destructive edits (type change, required-on, delete-with-data). Renames are advisory; v1 does not migrate values.
- **Per-field error round-trip:** widgets carry `data-field-path`; a 422 re-hydrates unsaved state from the patched entity and binds each dotted error (`bio`, `skills.2.label`) to its widget's `cms-field__error`. Flat in M-C2, nested in M-C3.
- **Rate limits / depth / cycles:** `MAX_DEPTH = 5` (AEM ≤5 discipline) enforced two ways — recursive-CTE structural guard at **attach time** (so cycles never persist) AND a path-scoped visited-set + depth cap in the **read resolver** (`truncated` flag, graceful stub, no exception in the public path).
- **Batching (N+1):** reference resolution is batched (`resolveMany`, one query per target collection per level) **and media/asset hydration is batched alongside it** — a repeater of N media-bearing rows resolves in one media query (M-C6, query-count test). Embeds batch via `loadEmbeddedEntries`.
- **Reference UX:** "Referenced by" sidebar panel + pre-delete impact interstitial (titles + links + per-policy consequence), not just 409/422 codes (M-C4).
- **Admin findability:** title/slug `LIKE` search on the admin entry index (M-C5) for large collections — distinct from the deferred at-scale facet indexing (§8).
- **Accessibility:** widgets use semantic controls (`<input>`/`<select>`/`<textarea>`, native `type=date`/`datetime-local`/`number`); the builder/picker modals reuse `createModal` (overlay/Escape/`×` dismiss, focus management); **reorder has a real keyboard mechanism — Move-up/down buttons + ArrowUp/Down handler — not just the pointer-only drag copied from `pages-index.mjs`**; every `cms-field` has a `<label>`; error messages render in `cms-field__error` tied to the field by `data-field-path`.
- **No-code-comments rule:** zero comments restating code anywhere. All WHY-rationale (single-JSON-payload, 1-level repeater cap, in-DOM-reorder-no-endpoint, junction-authoritative-JSON-cache, path-scoped cycle guard, the `updateAll` tenant-scope trap, singleton enforcement points, `beforeSave`-for-direct-assignment, `author_id`-not-accessible, orphaned-key tolerance) lives in `CLAUDE.md`/rule files, never in source.
- **Quality gate:** every slice runs `/quality` (cs-fix → PHPStan **max** → Rector dry → PHPUnit) green before merge. PHPStan max forces the recursive JSON shapes to be typed — centralize `FieldDefinition`/`EntryDataValue` array-shape PHPDoc on the entity + service signatures so recursion type-checks instead of leaking `mixed`. Bug fixes follow the two-commit Prove-It pattern (red commit before the fix commit). Non-trivial slices (M-C3, M-C4) route through `elegance-challenger` before presenting.

---

## 8. EXPLICIT NON-GOALS / DEFERRED (v2)

- **Entry & field i18n** (per-locale entry content, translated field labels, `I18n`/`translate` behavior) — **out of scope for v1** as a deliberate decision, not an omission. Entries are single-locale; revisit when a multilingual workspace need is concrete.
- **Schema / collection import-export & cross-workspace portability** — a schema's reference `target` is a **per-workspace slug**, so a schema is **not portable as-is**. Deferred; a future export/import must rewrite reference targets (and re-resolve media ids) on import. Recorded so portability is a decision, not a surprise.
- **Variations** (AEM master + named variations with master-fallback, `_variations`/`_variation`) — orthogonal to the type/instance core; defer.
- **GraphQL delivery** — the resolver already produces the exact nested-object shape GraphQL would expose; the JSON API (M-C7) is the v1 headless surface. GraphQL becomes a thin additive `CollectionGraphQLType` adapter over `EntryReferenceResolver` if a concrete external consumer needs client-driven field selection. Not a rewrite.
- **Entry revisions / history** (`collection_entry_revisions` + `EntryRevisionWriter`/`Restorer`) — Pages/Posts have `page_revisions`/`RevisionRestorer`; entries get **none** in v1. The `beforeSave` sanitizer choice already covers a future restore's direct assignment, so building this later needs no sanitizer change. Built only if authors ask for entry rollback.
- **Manual entry ordering** (`collection_entries.position` + a `reorder` endpoint) — defer unless drag-ordered entry lists are needed.
- **Custom-field sort/filter at scale** (`collection_entry_facets` / generated stored columns indexed per `"indexed": true` field) — the basic admin title/slug search ships in v1 (M-C5); `JSON_EXTRACT` ordering covers v1 listing; add facet indexing when a real "sort FAQ by priority at scale" need appears.
- **Cross-request resolver caching & page-cache embed invalidation** — v1 ships **request-scoped memoization only** (zero staleness, matches `BlockExpander`); there is **no page-output cache** today, so embedded-entry edits are immediately reflected (no invalidation gap). v2 may add a dependency-versioned resolver cache keyed by `{workspaceId, entryId, depth, publicOnly}` that re-validates referenced `modified` timestamps **and**, if a page-output cache is introduced, bumps the cache key of pages embedding an edited entry — only when profiling demands it.
- **Tags field** (central taxonomy), **JSON Object** pass-through field, **content-reference-to-arbitrary-asset** (vs fragment ref), **Markdown** multi-line mode (redundant with rich-text), **UUID-vs-path dual reference variants** (one internal id scheme — entry id), **tab-placeholder layout fields**, **custom composite-multifield** (replaced by the repeater + sub-collection-reference pattern) — all skipped or deferred per the AEM map.