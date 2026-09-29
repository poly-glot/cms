# Style — Declarative, Functional, Readable

Cross-language preferences. Read alongside `php.md` for any `.php` edit.

## Functional bent

- PREFER: **Pure functions** where possible. Same input → same output, no hidden state, no side effects.
- DO: **Isolate side effects at the edges** — controllers, repositories, mailers. Pure logic in the middle.
- PREFER: **`array_map` / `array_filter` / `array_reduce`** over manual `foreach` when transforming → new array.
- OK: **`foreach`** for side effects (dispatching events, logging). Don't force everything into pipes.

## Control flow

- DO: **Early returns first** — handle fast/error/empty cases at the top with early returns. Main logic follows unindented.
- DO: **Max nesting depth of 2.** If a method has three levels of `if`, extract a helper.
- NEVER: **Inline `if + return`** on one line. Always braces, always separate lines:

  ```php
  if ($user === null) {
      return null;
  }
  ```

- NEVER: **Mutate parameters.** Rebind or return a new value.

## Immutability

- DO: **Immutable collections by default.** When a method conceptually returns a modified collection, return a NEW collection — never mutate the receiver.
- DO: **`with()` pattern** for value-object updates:

  ```php
  $updated = $user->withEmail('new@example.com');
  ```

- NEVER: **`setX()` setters** on value objects. Setters belong on stateful services or models the framework already owns (Cake Entities).

## Naming

- DO: **Verbs for actions** (`createUser`, `sendEmail`, `chargeCard`).
- DO: **Nouns for accessors** (`fullName`, `totalAmount`).
- DO: **`is`/`has`/`can` for booleans** (`isActive`, `hasPermission`, `canRefund`).
- DO: **PSR-4 + one class per file.** Filename = class name.
- NEVER: **Abbreviations.** `$user` not `$usr`. `$response` not `$resp`. `$index` not `$idx`. Exceptions: well-known terms like `$id`, `$url`, `$dto`.
- NEVER: **Single-letter variables** except `$i` for trivial integer loops (and even those are usually unnecessary with `array_map`).

## Readability over cleverness

- NEVER: **One-liner clever solutions** — chained `??` with nested ternaries, etc. Explicit control flow that reads top-to-bottom.
- DO: **Extract to a named local** when an expression takes more than one mental step to parse:

  ```php
  $eligibleForDiscount = $user->isPremium() && $cart->total() > 100;
  if ($eligibleForDiscount) {
      // ...
  }
  ```
