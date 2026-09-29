# Security

## Input validation

- DO: **All user input validated at the controller boundary** via Form objects or Entity validation (validation rules on the Table).
- DO: **Validate before save**, never trust the type system alone — types catch shape, validation catches values.

## Mass assignment

- DO: **`Entity::$_accessible` whitelist explicit per field.** Never `'*' => true`:

  ```php
  protected array $_accessible = [
      'email' => true,
      'password' => true,
      'is_admin' => false,    // explicitly NOT mass-assignable
  ];
  ```

- BLOCKER: **`'*' => true` is a code-review blocker.** Always.

## SQL

- DO: **Query Builder with placeholders only:**

  ```php
  $this->Users->find()->where(['email' => $email])->first();
  ```

- NEVER: **String interpolation in `where()` / `having()` / `order()` / `orWhere()` etc.**:

  ```php
  // BLOCKER — injection vector
  $this->Users->find()->where("email = '$email'")->first();
  ```

- DO: **If you must write SQL fragments, use `IdentifierExpression` and bound params:**

  ```php
  use Cake\Database\Expression\QueryExpression;
  $query->where(fn (QueryExpression $exp) => $exp->eq('email', $email));
  ```

## Authentication and passwords

- DO: **Password hashing via `Cake\Auth\DefaultPasswordHasher`** (Argon2id under the hood). Never roll your own.
- DO: **Authentication plugin** (`Authentication`) for login flows. Never roll your own session.
- DO: **Authorization plugin** (`Authorization`) with policies in `src/Policy/`. One policy per resource; entities that share a rule map to one shared policy (`AuthoredContentPolicy`, `EditorialPolicy`) in `Application::policyResolver()`.
- NEVER: **Inline auth checks** like `if ($user->id !== $resource->user_id) ...` in controllers. Use a policy.

## CSRF and forms

- DO: **CSRF middleware on by default** in `Application::middleware()`. Scaffold ships this on.
- DO: **`skipCsrfCheck()` requires a comment** explaining the exception (typically webhooks with HMAC signature verification).
- DO: **FormProtection middleware on by default** for traditional form posts.

## Secrets

- DO: **`Configure::read('App.secret')`** for secrets, loaded from env vars in `config/app_local.php`.
- NEVER: **String literals for secrets** in source. Not even "this will be overridden by env" placeholders.
- DO: **`config/app_local.php` gitignored** — scaffold ships it gitignored; verify.

## File uploads

- DO: **Validate four things:** mime type (server-detected, not client-claimed), size, extension, magic bytes.
- DO: **Store outside webroot.** Use Cake's filesystem with a non-webroot path; serve via controller with auth.
- NEVER: **Trust the filename.** Generate a UUID; store original in DB if needed for display.

## Output

- DO: **Template output escaping is the default.** `<?= h($var) ?>` or just `<?= $var ?>` if Cake auto-escapes (verify per template engine).
- DO: **Raw output `<?= $var ?>` (when not auto-escaped) requires justification** — a one-line comment explaining the source is already-safe HTML.

## Rich-text body (pages/posts)

- DO: **The page/post `body` is rendered RAW to anonymous visitors** (`$this->Block->expand($body)`), so it is sanitized at the save boundary, not at render. `App\Service\Page\BodySanitizer` (HTMLPurifier allowlist) runs in `EditorialContentBehavior::beforeSave` (attached to `PagesTable` and `PostsTable`) gated on `isDirty('body')` — `beforeSave`, not `beforeMarshal`, because `PagePublisher::restore` patches `$page->body` straight from a revision without marshalling.
- NEVER: **Trust the editor's client schema as the boundary.** An authenticated author can PATCH arbitrary HTML to `pages/autosave`; the server sanitizer is the real control.
- DO: **Bump `HTML.DefinitionRev` in `BodySanitizer`** whenever the allowlist or the `data-block` custom attribute changes, or the serialized HTMLPurifier definition cache goes stale.

## Dependencies

- DO: **Run `composer audit` regularly.** `/security-audit` invokes it.
- DO: **Pin major versions in `composer.json`** (`^2.0`, not `*`).
- DO: **Review every new `require` line** before adding. Maintenance + license + reputation.
