# Architecture — `src/` Layout

## Directory responsibilities

| Folder | Responsibility |
|---|---|
| `src/Controller/` | HTTP entry points. Thin. Orchestrate input → service/table → response. |
| `src/Model/Table/` | Query layer, validation (`validationDefault`), rules (`buildRules`), finders. |
| `src/Model/Entity/` | Domain entities. Derived state (property hooks / `_getFoo`), accessor logic. |
| `src/Service/` | Orchestration across tables or external systems. Transactional flows. |
| `src/Command/` | CLI commands (replaces Cake 3 "Shell"). |
| `src/Middleware/` | Cross-cutting HTTP concerns (logging, CORS, request ID). |
| `src/Policy/` | Authorization policies (Authorization plugin). One per resource, or one shared policy mapped to several entities in `Application::policyResolver()`. |
| `src/Event/` | Listeners for app-wide events. |
| `src/Exception/` | Custom exception types per domain. |
| `templates/` | Mirrors controller structure; one folder per controller. |

## Where does a new class go?

| Question | Answer |
|---|---|
| Reads from one table, returns query results? | `src/Model/Table/` finder method |
| Derived from an entity's own fields? | `src/Model/Entity/` property hook |
| Coordinates multiple tables in a transaction? | `src/Service/<Feature>/` |
| Side-effecting cross-cutting concern (audit, cache)? | `src/Event/` listener |
| Authorization decision? | `src/Policy/<Resource>Policy.php`, or map the entity to an existing shared policy (`AuthoredContentPolicy`, `EditorialPolicy`) in `Application::policyResolver()` |
| Custom HTTP middleware? | `src/Middleware/` |
| CLI command? | `src/Command/` |

## Don't

- NEVER: **Feature folders inside `src/`** (e.g. `src/Users/`, `src/Billing/`). CakePHP convention is by-kind, not by-domain. Resist this even if you've used by-feature elsewhere — it fights the framework. Sub-folders inside `Service/` are fine (`src/Service/User/`, `src/Service/Billing/`).
- NEVER: **Plugins to organize internal features.** Plugins are for shippable, reusable units. Use sub-folders in `src/Service/` instead.
- NEVER: **Co-locate a service inside a controller folder.** If a class is a service, it goes in `src/Service/`. Co-location signals coupling that almost never holds.

## Templates

- DO: **`templates/<Controller>/<action>.php`** mirrors `src/Controller/<Controller>Controller.php::<action>()`.
- DO: **Shared layouts in `templates/layout/`**, partials in `templates/element/`.
- NEVER: **Business logic in templates.** Render only. Decisions in the controller or view cell.

## When to introduce a Service

A Table method is enough when:
- The operation reads from or writes to one table only.
- Validation and rules cover the constraints.

Pull out a Service when:
- The operation touches two or more tables in one transaction.
- There's an external dependency (HTTP call, queue dispatch, file upload).
- There's significant domain logic beyond "validate and save".

If you're unsure, start in the Table and extract when a second use case appears.
