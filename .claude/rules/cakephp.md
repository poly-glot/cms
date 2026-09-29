# CakePHP 5 Conventions

Canonical reference: https://book.cakephp.org/5.x/

## Layering

- DO: **Controllers thin** — orchestration only, no business logic. Parse input → call Service or Table method → return response. No `if`-trees over domain state.
- DO: **Table classes own queries** — define finders (`findActive`, `findByEmail`) on the Table; never write `$this->find()->where(...)` inline in a controller.

  ```php
  // src/Model/Table/UsersTable.php
  public function findActive(SelectQuery $query): SelectQuery
  {
      return $query->where(['Users.is_active' => true]);
  }

  // Controller
  $users = $this->Users->find('active')->all();
  ```

- DO: **Entities own derived state** — virtual fields via PHP 8.4 property hooks, or `_getFoo()` for older patterns. Logic that depends only on the entity's own data belongs on the entity.
- DO: **Service layer in `src/Service/`** for orchestration across multiple tables, external systems, or transactional flows.

## Validation and rules

- DO: **Validation in `Table::validationDefault()`** (format / type / presence — the "is this even a well-formed value?" layer).
- DO: **Rules in `Table::buildRules()`** (uniqueness / FK existence — the "does this make sense given other rows?" layer).
- NEVER: **Validate in the controller.** If a controller is checking field shape, the validation belongs on the Table.

## SQL

- NEVER: **Raw SQL** unless impossible via Query Builder.
- DO: **If raw SQL is required, use `bind()` placeholders:**

  ```php
  $connection->execute('SELECT * FROM users WHERE email = :email', ['email' => $email]);
  ```

- NEVER: **String interpolation in `where()`, `having()`, `order()` clauses.** That's an SQL-injection vector.

## Scaffolding (`bin/cake bake`)

- DO: **Treat `bake` output as a starting point.** Always:
  1. Strip generated comments (forbidden per `php.md`).
  2. Remove unused methods (delete what you don't immediately need).
  3. Apply `php.md` and `style.md` rules.
  4. Run `composer cs-fix` then `/quality --changed`.
- USE: `/bake` slash command to wrap the above workflow.

## Middleware

- DO: **CSRF middleware enabled by default** in `src/Application.php`. The scaffold ships it on; don't disable.
- DO: **Add `Authentication` and `Authorization` plugins** when login flows are introduced. `composer require cakephp/authentication cakephp/authorization` then wire the middleware in `Application::middleware()`. The scaffold does NOT ship these — they're added on demand.
- DO: **FormProtection** for traditional form posts (component-level in controllers, opt-in per action).
- DO: **Document any `skipCsrfCheck()`** with a one-line comment explaining why (typically webhook endpoints with HMAC verification).

## Config and secrets

- DO: **`Configure::read('App.foo')`** for config access. Define in `config/app.php` / `config/app_local.php`.
- NEVER: **`getenv()` outside `config/`.** App code reads from `Configure`; config files read from env.
- DO: **Secrets in `config/app_local.php`** (gitignored) or env vars. Never literals in source.

## Routing

- DO: **Routes in `config/routes.php`**, resourceful by default (`$builder->resources('Users')`).
- NEVER: **Inline closures as route handlers.** Always controller actions — they're testable and trace-able.

## Events

- DO: **Events for cross-cutting concerns** — audit logging, cache invalidation, notifications. Don't reach across tables in a Service when an event listener can do it.
- DO: **Listeners in `src/Event/`**, one per concern.

## Plugins

- DO: **Plugins for shippable, reusable units** (e.g., a multi-tenant module a separate app could install).
- NEVER: **Plugins for organizing internal features.** Internal features live in `src/Service/<Feature>/`. Plugins add deployment overhead — only use them when there's reuse.
