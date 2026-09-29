# Cabinet

Cabinet is a multi-workspace content management system built on CakePHP 5.3, PHP 8.4 and MySQL 8. Each workspace gets a
public site, an admin area, a GraphQL API and a file-based content workflow, so pages, posts and collection entries can
live in version control and be imported by CI.

The admin area is server-rendered CakePHP templates enhanced with vanilla ES modules and plain CSS. Application code has
no build step. Node is used only to bundle the rich-text editor, copy two vendored libraries, lint, and run the browser
tests.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Getting started](#getting-started)
- [Configuration](#configuration)
- [Working with content](#working-with-content)
- [GraphQL API](#graphql-api)
- [Development](#development)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Licence](#licence)

## Features

### Content

- Pages form a tree. A child's URL follows the slug chain (`/about/press`), and you reorder the tree by dragging. Each
  page uses one of three templates: `default`, `long_form` or `landing`.
- Posts are a flat journal, listed at `/blog` and read at `/blog/{slug}`, with an excerpt for feeds and previews.
- Collections are content types you define in the admin. A schema is a list of fields drawn from twelve types: text,
  textarea, rich text, number, date, datetime, boolean, select, media, tags, reference and repeater. Entries store their
  values against that schema. Pages and posts can carry custom fields the same way.
- Pages, posts and entries move between draft, live and scheduled. A cron command publishes pages whose scheduled time
  has passed.
- Every Save Draft or Publish on a page writes a revision, and you can restore any of them from the editor. The editor
  autosaves three seconds after you stop typing.
- Reusable blocks come in six kinds: callout, quote, statistic, call to action, image and file. A page body holds a
  reference to the block rather than a copy, so editing the block changes every page that uses it, and the system
  refuses to delete a block that is still in use.
- Tags attach to pages, posts and entries and autocomplete as you type.

### Editing

- The TipTap editor provides headings, lists, alignment, links, tables, images with size renditions and alt text, and
  block insertion through a picker.
- Rich-text bodies pass through HTMLPurifier when saved. The public site renders the stored HTML as is.

### Media

- Uploads are checked by detected MIME type, matching extension and size: PNG, JPEG, GIF and WebP up to 10 MB, PDF up to
  50 MB, plain text up to 5 MB.
- Files live outside the web root under `storage/uploads` and are served through the application by UUID, never by their
  original name.
- Each image gets `large` (1920 px), `medium` (960 px), `small` (480 px) and a 240 × 240 `thumb` rendition at upload
  time.
- The library offers search, type filters, a detail view for alt text, and a picker that block forms and collection
  fields reuse.

### Comments

- The public endpoint accepts comments on pages, posts and entries. The built-in templates show the form on pages and
  posts, and only when the site-wide setting and the item's own toggle both allow it.
- The moderation queue supports approve, archive, spam, delete, bulk approve, mark all read and a moderator reply.
  Comments are pending, approved, spam or archived.

### Navigation and appearance

- The menu builder manages several menus per workspace. Items nest by drag and drop and link to a page or an external
  URL. A save replaces the whole tree in one transaction.
- Three themes ship: `heritage`, `atelier-dark` and `paper`. Each is a palette layered over one base stylesheet.
- Settings cover the site title, tagline, theme, whether comments are open, and whether new comments wait for
  moderation.

### Users and workspaces

- Signing up creates a user and their first workspace. A user can create further workspaces at `/workspaces/new`. Every
  workspace has its own URL prefix, `/{workspace}/...`, and its own content, media, menus, settings and members.
- Membership carries one of four roles: admin, editor, author or contributor. Admins and editors publish and moderate.
  Authors and contributors edit only their own content and cannot publish. Settings, appearance, users, collections and
  navigation are admin-only.
- Admins invite members by email with a link that stays valid for seven days. Password reset and an account page are
  included.

### API

- GraphQL is served at `POST /{workspace}/graphql` and authenticated with personal access tokens carrying `read`,
  `preview` or `write` scope.
- Queries cover pages, posts, collections and entries, menus, media, tags, comments, blocks, settings and the workspace.
  Mutations create, update, delete, publish and schedule pages and posts, manage entries, and approve or spam comments.
- A GraphiQL playground in the admin ships schema documentation, runnable examples and a button that mints a one-hour
  token.

### Content as code

- Export pages, posts and entries to YAML under `content/{workspace}/`, and collection schemas to `config/collections/`.
- Import compares a hash of each file with the last import and skips anything unchanged, so you can run it on every
  deploy. CI imports the committed content and proves that a second run changes nothing.

### Security

- Every response carries a Content-Security-Policy with `script-src 'self'` and no inline scripts, plus
  `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy`.
- CSRF tokens protect every form and XHR. In production the application checks the Host header against
  `APP_FULL_BASE_URL`.
- A token-bucket rate limiter allows one request per second with a burst of ten on sign-in and public form posts, and
  500 per second on public content and admin XHR.
- Authorisation policies guard each resource, every entity whitelists its assignable fields, and uploads are validated
  by magic bytes rather than the client's claim.

## Requirements

- PHP 8.4 or later with the `intl`, `mbstring`, `pdo_mysql`, `gd` and `fileinfo` extensions.
- MySQL 8. The devcontainer and CI use 8.4.
- Composer 2.
- Node 22 and npm, for the editor bundle, linting and browser tests only. The application runs without Node.

## Getting started

### Devcontainer

Open the repository in VS Code with the Dev Containers extension. The container builds PHP 8.4 with the required
extensions, starts MySQL, runs `composer install`, installs Playwright with Chromium, and starts the dev server on port
8765. Then create the schema and sample data:

```bash
bin/cake migrations migrate
bin/cake seeds run InitialSeed
```

### On your own machine

```bash
composer install
npm ci
mysql -e 'CREATE DATABASE cms CHARACTER SET utf8mb4'
export DATABASE_URL='mysql://user:password@127.0.0.1:3306/cms?encoding=utf8mb4'
bin/cake migrations migrate
bin/cake seeds run InitialSeed
bin/cake server -p 8765
```

`composer install` copies `config/app_local.example.php` to `config/app_local.php` on first run. `DATABASE_URL`
overrides the datasource in that file. The example file uses the Debug mail transport, so invitations and password
resets are logged rather than sent.

### First sign-in

Open `http://localhost:8765/login`. The seed creates one workspace, `cabinet`, with four members. Every account uses the
password `dev-password-1` unless you set `CABINET_SEED_PASSWORD` before seeding.

| Email                   | Role        |
|-------------------------|-------------|
| `admin@cabinet.local`   | admin       |
| `eleanor@cabinet.local` | editor      |
| `marcus@cabinet.local`  | author      |
| `jun@cabinet.local`     | contributor |

The admin area is at `/cabinet/admin`; the public site starts at `/cabinet/about` and `/cabinet/blog`. To start from an
empty workspace instead, sign out and use `/signup`.

## Configuration

Configuration is read from environment variables in `config/app.php` and `config/app_local.php`.

| Variable                                                         | Purpose                                                                                                                                      |
|------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------|
| `DATABASE_URL`                                                   | Datasource DSN. For an empty password write `mysql://user:@host/db`; a URL with no password segment keeps the password from `app_local.php`. |
| `DATABASE_TEST_URL`                                              | Datasource for PHPUnit.                                                                                                                      |
| `SECURITY_SALT`                                                  | Required. Seeds password hashing and session security.                                                                                       |
| `DEBUG`                                                          | `true` in development. Must be `false` in production.                                                                                        |
| `APP_FULL_BASE_URL`                                              | Required in production. Requests whose Host header differs are rejected; without it the application refuses to serve.                        |
| `EMAIL_TRANSPORT_DEFAULT_URL`                                    | Mail transport DSN, for example `smtp://user:pass@mail.example.com:587`.                                                                     |
| `CABINET_SEED_PASSWORD`                                          | Password given to the seeded accounts.                                                                                                       |
| `APP_DEFAULT_TIMEZONE`, `APP_DEFAULT_LOCALE`, `APP_ENCODING`     | Default to `UTC`, `en_US` and `UTF-8`.                                                                                                       |
| `CACHE_DEFAULT_URL`, `CACHE_CAKECORE_URL`, `CACHE_CAKEMODEL_URL` | Cache engine DSNs. File cache by default.                                                                                                    |
| `LOG_DEBUG_URL`, `LOG_ERROR_URL`                                 | Log engine DSNs. Files under `logs/` by default.                                                                                             |

`storage/uploads` receives uploaded files and must be writable by the web server user.

## Working with content

### Scheduling

Choose a future date in the Publish menu. A cron entry publishes scheduled pages once their time arrives:

```
* * * * * /path/to/app/bin/cake pages publish-scheduled
```

Posts and entries carry the scheduled state too, but the command handles pages only at present.

### Content as code

```bash
bin/cake content_export --workspace=cabinet                   # writes content/cabinet/**/*.yml
bin/cake content_import --workspace=cabinet                   # imports files whose hash changed
bin/cake content_import --workspace=cabinet --type=posts --force
bin/cake schema_import  --workspace=cabinet                   # config/collections/*.yml into collections
```

Both content commands accept `--type` (`posts`, `pages`, `entries` or `all`), `--collection=<slug>` and `--root=<dir>`.
`content_import` and `schema_import` take `--force` to ignore the hash gate. Run `schema_import` before `content_import`
when entries belong to a new collection.

A page file:

```yaml
title: Contact
slug: contact
status: live
body: '<p>Reach the workshop by post or by phone.</p>'
template: default
visibility: public
published_at: null
comments_enabled: false
position: 0
parent: null
author: admin@cabinet.local
tags: []
data: []
```

A collection schema:

```yaml
name: Category
slug: category
description: "A product category used to group related products."
fields:
  - name: headline
    label: Headline
    type: text
    required: true
  - name: featured
    label: Featured
    type: boolean
```

## GraphQL API

Create a token at `/{workspace}/admin/tokens`. Scopes rank `read` < `preview` < `write`, and a higher scope includes the
lower ones. `preview` reveals draft and scheduled content. `write` enables mutations, which also respect the token
owner's role in the workspace.

```bash
curl -X POST https://example.com/cabinet/graphql \
  -H 'Authorization: Bearer <token>' \
  -H 'Content-Type: application/json' \
  -d '{"query":"{ posts(perPage: 5) { items { title slug status } pageInfo { total hasNextPage } } }"}'
```

The playground at `/{workspace}/admin/api-playground` runs GraphiQL against the same endpoint, with schema
documentation, a menu of example queries and a button that issues a temporary token valid for one hour.

## Development

### Layout

| Path                                  | Holds                                                                                  |
|---------------------------------------|----------------------------------------------------------------------------------------|
| `src/Controller/`                     | HTTP entry points. Admin controllers sit under `Admin/`.                               |
| `src/Model/`                          | Tables, entities, enums and the tenant context.                                        |
| `src/Service/`                        | Transactional flows across tables: publishing, uploads, imports, invitations, sign-up. |
| `src/Policy/`                         | Authorisation policies.                                                                |
| `src/Middleware/`                     | Security headers, Host validation, rate limiting, tenant resolution.                   |
| `src/GraphQL/`                        | Schema, types, queries, mutations and batch loaders.                                   |
| `src/Command/`                        | CLI commands.                                                                          |
| `templates/`                          | Views, one folder per controller, plus shared elements.                                |
| `webroot/js/`, `webroot/css/`         | Admin modules and stylesheets, served as written.                                      |
| `config/Migrations/`, `config/Seeds/` | Schema history and sample data.                                                        |
| `config/collections/`                 | Collection schemas as code.                                                            |
| `content/`                            | Exported content, one folder per workspace.                                            |
| `tests/TestCase/`, `tests/E2E/`       | PHPUnit and Playwright suites.                                                         |
| `.claude/rules/`                      | Coding conventions for PHP, CakePHP, style, testing, security and the frontend.        |

Read the rule file for the area you are about to change. The frontend one documents the module kit, the data-attribute
registry and the lint rules.

### Frontend

- Modules under `webroot/js` are served as written. Shared primitives (HTTP, DOM, modal, drag, slug, suggest) live in
  `webroot/js/admin/kit/`.
- Behaviour hooks onto `data-*` attributes, never CSS classes. ESLint fails the build on a `.cms-` selector in
  JavaScript.
- `webroot/js/admin/vendor/tiptap.entry.js` is the editor's build input. After changing it run `npm run build:editor`
  and commit the bundle.
- GraphiQL and React are copied from exact-pinned npm packages by `npm run vendor:graphiql`. CI rebuilds both vendored
  artefacts and fails if the committed files differ.
- Stylesheets are one declaration per line, alphabetical, in cascade layers. Prettier and Stylelint enforce it.

### Quality gate

```bash
composer quality      # php-cs-fixer check, PHPStan at level max, Rector dry run, PHPUnit
npm run lint          # Prettier check, ESLint, Stylelint
npm run lint:fix      # apply every autofix
composer test         # PHPUnit only
```

PHPUnit needs `DATABASE_TEST_URL` to point at a database it can rebuild.

### Browser tests

```bash
npx playwright install chromium
npm run test:e2e
```

Playwright starts `bin/cake server` on port 8765, or reuses one already running, and needs a migrated and seeded
database reachable through `DATABASE_URL`. The specs reset fixtures through the `mysql` client and clear the rate-limit
cache before each sign-in.

### Continuous integration

Every push and pull request runs four jobs: the PHP quality gate, a content import that proves the hash gate is
idempotent, the frontend lint and vendored-artefact check, and the Playwright suite against a migrated and seeded
application.

## Deployment

1. Install without development packages: `composer install --no-dev --optimize-autoloader`. Node is not needed on the
   server; the editor bundle and vendored libraries are committed.
2. Set `DEBUG=false`, `SECURITY_SALT`, `DATABASE_URL`, `APP_FULL_BASE_URL` and `EMAIL_TRANSPORT_DEFAULT_URL`.
3. Run `bin/cake migrations migrate`.
4. Make `tmp/`, `logs/` and `storage/` writable by the web server user.
5. Point the document root at `webroot/`. The shipped `.htaccess` rewrites requests to `index.php`, declares
   `text/javascript` for `.mjs` and sends `Cache-Control: no-cache` for them. Asset URLs carry the file's modification
   time, but a module's own `import` URLs do not, so the header is what makes a changed module reach browsers on the
   next load. Mirror both directives on nginx.
6. Add the cron entry from [Scheduling](#scheduling).
7. For more than one server, move the cache to Redis or APCu through the `CACHE_*_URL` variables.

## Troubleshooting

- **429 responses while testing or clicking quickly.** The rate limiter rejected a burst. Clear it with
  `bin/cake cache clear ratelimit`.
- **Module scripts refuse to load on Apache with a MIME error.** The server is missing the `.mjs` mapping. The shipped
  `.htaccess` adds it; on nginx add `text/javascript mjs;` to the `types` block.
- **"Access denied ... (using password: YES)" although the DSN has no password.** CakePHP merges the URL over the array
  in `app_local.php`, so the array's password wins. Write an explicit empty password: `mysql://root:@127.0.0.1/db`.
- **Invitation or reset emails never arrive.** `app_local.php` uses the Debug transport. Set
  `EMAIL_TRANSPORT_DEFAULT_URL`.
- **An editor change does not appear.** Rebuild the bundle with `npm run build:editor`; the browser loads the committed
  `tiptap.bundle.mjs`, not the entry file.

## Licence

MIT, as declared in `composer.json`.
