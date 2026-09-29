---
name: explore-codebase
description: >
  CakePHP-aware orientation for a feature, layer, endpoint, or model. Reads
  config/routes.php, src/Application.php, composer.json, then traces from the
  user's entry point outward through controller → service → table → entity →
  templates → fixtures → tests. Read-only.
---

# Explore Codebase

Onboarding orientation. Read-only.

## Step 1 — Get the entry point

Ask: "What are you trying to understand?" Offer these starting points:

- **A route** (e.g. `POST /api/users`) — start from `config/routes.php`.
- **A feature** (e.g. "user registration") — search for the feature name in `src/`.
- **A model** (e.g. `Users`) — start from `src/Model/Table/UsersTable.php`.
- **A layer** (e.g. "all middleware") — list files in `src/Middleware/`.

## Step 2 — Read the framework hooks

Read these three files first to ground yourself:

```bash
cat composer.json | jq '.require'    # which libraries are in play
cat src/Application.php              # middleware order, bootstrap
cat config/routes.php                # route → controller map
```

## Step 3 — Trace from entry point

### For a route

1. Find the route in `config/routes.php`. Note the resolved controller + action.
2. Read the controller method. Note any Services used, any Tables used.
3. For each Service / Table / Entity, read the relevant methods.
4. Find the template (`templates/<Controller>/<action>.php` for non-JSON responses).
5. Find the test (`tests/TestCase/Controller/<Controller>ControllerTest.php`).

### For a model

1. Read the Table class — note finders, validation, rules, associations.
2. Read the Entity class — note virtual fields, hidden fields, accessible whitelist.
3. Grep for consumers: `grep -r 'Users->find' src/ tests/`.
4. Find the fixture (`tests/Fixture/UsersFixture.php`).
5. Find related migrations (`config/Migrations/*Users*`).

### For a feature

1. Grep for the feature name in `src/`. Likely candidates: `src/Service/<Feature>/`, `src/Controller/<Feature>Controller.php`, `src/Model/Table/<Feature>Table.php`.
2. Trace each from above.

## Step 4 — Report

Output a short bulleted tree of the call graph:

```
POST /api/users  (config/routes.php:34)
├── UsersController::add  (src/Controller/UsersController.php:42)
│   ├── UserRegistrationService::register  (src/Service/User/UserRegistrationService.php:21)
│   │   ├── UsersTable::save  (Cake built-in, validation: validationDefault)
│   │   └── Welcome email dispatch  (src/Event/UserRegistered.php listener)
│   └── Returns: 201 Created with UserResource (src/Resource/UserResource.php)
├── Tests: tests/TestCase/Controller/UsersControllerTest.php:testAddCreatesUser
├── Fixtures: tests/Fixture/UsersFixture.php
└── Authorization: src/Policy/UserPolicy.php::canRegister
```

Highlight surprises (mismatched naming, missing tests, layers skipped). Don't write a thesis — keep the report scannable.

## Rules

- Read-only. No edits, no commits, no writes.
- One question to start (entry point); then trace and report.
- Don't speculate about why something is structured a certain way — report what's there, not what should be.
