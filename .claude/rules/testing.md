# Testing — PHPUnit + CakePHP Test Traits

## Structure

- DO: **One `*Test.php` per class**, mirroring `src/` under `tests/TestCase/`:

  ```
  src/Service/User/RegistrationService.php
  tests/TestCase/Service/User/RegistrationServiceTest.php
  ```

- DO: **Test names read as specifications:** `testCreatesUserWithValidEmail`, `testRejectsDuplicateEmail`. Avoid generic names like `testCreate`.

## Test levels

| Code under test | Test type | Traits |
|---|---|---|
| Pure logic (Service, Entity, Value object) | Unit | None |
| Controller (HTTP boundary) | Integration | `IntegrationTestTrait` |
| CLI command | Console integration | `ConsoleIntegrationTestTrait` |
| Database touched | Use fixtures + appropriate trait above | Never mock the Table under test |

## Fixtures

- DO: **Fixtures in `tests/Fixture/`**, minimal records (3-5 rows), declarative `$records` arrays.
- DO: **Load via `protected array $fixtures = ['app.Users', 'app.Posts'];`**
- NEVER: **Use a fixture as a data source for assertions.** Hardcode expected values in the test — fixtures change.

## Mocks

- DO: **Mock at system boundaries** — HTTP clients, mailers, queue dispatchers, payment gateways, file storage.
- NEVER: **Mock the Table you're testing.** Use fixtures.
- NEVER: **Mock the class under test** ("partial mocks"). If you need that, the class is doing too much.

## Test structure

- DO: **Arrange / Act / Assert**, with blank lines between phases:

  ```php
  public function testCreatesUserWithValidEmail(): void
  {
      $service = $this->getUserService();
      $payload = ['email' => 'a@b.c', 'password' => 'secret123'];

      $user = $service->register($payload);

      $this->assertSame('a@b.c', $user->email);
      $this->assertTrue($user->isActive);
  }
  ```

- DO: **One concept per test.** Multiple `assert*` calls are fine if they verify one behavior.
- NEVER: **Snapshot tests** unless reviewing every change to the snapshot.
- NEVER: **Test private methods directly.** Test through the public API.
- NEVER: **Test the framework.** Don't assert `$this->Users->find()` works — assume CakePHP works. Test YOUR finder logic.

## Bugs — Prove-It pattern

- DO: **Every bug-fix PR has two commits:**
  1. A failing test that demonstrates the bug.
  2. The fix.

  This makes the regression visible and ensures the bug doesn't silently return.

- USE: `/test bug "<description>"` to dispatch the test-engineer agent for step 1.

## Running tests

```bash
composer test                                    # full suite
vendor/bin/phpunit tests/TestCase/Service/...    # one directory
vendor/bin/phpunit --filter testRejects...       # one method
composer test-coverage                           # with text coverage report
```
