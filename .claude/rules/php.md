# PHP 8.4 Conventions

Idioms for modern PHP 8.4 code. Read before any `.php` edit.

## Strict types

- DO: **`declare(strict_types=1);` on every PHP file**, immediately after the opening `<?php` tag.
- DO: **Typed properties everywhere** — no untyped, no `mixed` unless boundary-justified (e.g. wrapping a `json_decode` result).
- DO: **Typed parameters and return types on every function and method.**

## Constructor and properties

- DO: **Constructor property promotion** for DTOs and value objects:

  ```php
  final readonly class UserCreated
  {
      public function __construct(
          public string $id,
          public string $email,
          public \DateTimeImmutable $createdAt,
      ) {}
  }
  ```

- DO: **`readonly` on every field that's not reassigned** after construction. For whole-object immutability, mark the class `readonly`.
- PREFER: **Asymmetric visibility** (`public private(set) string $foo`) over manual getters when callers should read but not write:

  ```php
  final class Account
  {
      public function __construct(public private(set) string $status = 'pending') {}
      public function activate(): void { $this->status = 'active'; }
  }
  ```

- PREFER: **Property hooks** for derived state instead of `getFoo()`:

  ```php
  final class User
  {
      public function __construct(public string $firstName, public string $lastName) {}
      public string $fullName { get => $this->firstName . ' ' . $this->lastName; }
  }
  ```

## Enums

- PREFER: **Backed `enum`** over string constants for closed sets:

  ```php
  enum OrderStatus: string {
      case Pending = 'pending';
      case Paid = 'paid';
      case Refunded = 'refunded';
      public function isTerminal(): bool { return match($this) { self::Paid, self::Refunded => true, default => false }; }
  }
  ```

- DO: **Native enum methods over switch.** Use `match` or instance methods.

## Return types and absence

- DO: **`never`** return type for throw-only paths.
- PREFER: **`null` over `false`** for "no result". Never `int|false`; use `?int` or `int|null`.
- PREFER: **Type union over nullable when both states are meaningful** — `Result|ValidationError` over `?Result`.

## Match and callables

- DO: **`match` over `switch`** for value mapping. `match` is strict-comparison and returns a value.
- DO: **First-class callable syntax** (`$obj->method(...)`) over closures wrapping a single call.

## PHPDoc

- DO: **PHPDoc only for what the type system can't express:** generics (`@template`, `@param array<int, Foo>`), behavior contracts, or non-obvious side effects.
- NEVER: **PHPDoc that restates the type signature** (`@param string $email`). It's noise and rots when the signature changes.

## Forbidden

- NEVER: **Comments that restate code** — let names carry meaning. Reserve comments for non-obvious *why*.
- NEVER: **`console.log` equivalent**: no `var_dump`, `print_r`, `dd`, `dump` left in committed code. Use `Cake\Log\Log::debug()` for debug logging.
- NEVER: **`@`-suppression** of errors. Fix the error or catch the exception.
- NEVER: **`global` keyword.** Inject the dependency.
