# Error Handling

## Exception hierarchy

- DO: **Custom exception types per domain**, in `src/Exception/`. Extend SPL exceptions, not `\Exception` directly:

  ```php
  namespace App\Exception;
  final class UserAlreadyExistsException extends \DomainException {}
  final class RegistrationFailedException extends \RuntimeException {}
  ```

- DO: **HTTP exceptions in controllers only** — `Cake\Http\Exception\NotFoundException`, `BadRequestException`, `UnauthorizedException`. Services raise domain exceptions; controllers translate.

## Catching

- NEVER: **`catch (\Exception $e)`** without (a) re-throw with context, OR (b) logged context.

  ```php
  // Bad
  try { ... } catch (\Exception $e) {}

  // OK
  try { ... } catch (\Throwable $e) {
      Log::error('Failed to send welcome email', ['exception' => $e, 'user_id' => $userId]);
      throw new RegistrationFailedException('Welcome email send failed', previous: $e);
  }
  ```

- DO: **Empty catch blocks add a comment** explaining why:

  ```php
  try { $cache->delete($key); } catch (\Throwable) {
      // noop: cache invalidation is best-effort; failure must not block the response
  }
  ```

## Translating domain → HTTP

- DO: **Services throw domain exceptions; controllers translate to HTTP:**

  ```php
  // Controller
  try {
      $this->users->register($payload);
      return $this->response->withStatus(201);
  } catch (UserAlreadyExistsException) {
      throw new ConflictException('Email already registered');
  } catch (ValidationException $e) {
      throw new BadRequestException($e->getMessage());
  }
  ```

- DO: **Validation failures → 422.** Use `ValidationException` from Service, controller translates.

## What never to do

- NEVER: **Return `false` to signal an error** when the method also returns useful data. Throw or return a sum type.
- NEVER: **Swallow exceptions silently.** Either re-throw, log + re-throw, or log + comment why.
- NEVER: **Use exceptions for control flow** (e.g., throwing to break out of a loop). Use return.
