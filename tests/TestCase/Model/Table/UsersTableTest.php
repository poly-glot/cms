<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;
use DomainException;
use Override;

final class UsersTableTest extends TestCase
{
    protected array $fixtures = ['app.Users'];
    private UsersTable $Users;

    #[Override]
    protected function getFixtureStrategy(): TransactionStrategy
    {
        return new TransactionStrategy();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = $this->fetchTable('Users');
    }

    public function testRequiresEmailAndPasswordAndName(): void
    {
        $user = $this->Users->newEntity([]);

        $this->assertArrayHasKey('email', $user->getErrors());
        $this->assertArrayHasKey('password', $user->getErrors());
        $this->assertArrayHasKey('name', $user->getErrors());
    }

    public function testRejectsInvalidEmail(): void
    {
        $user = $this->Users->newEntity(['email' => 'not-an-email', 'password' => 'secret123', 'name' => 'X']);

        $this->assertArrayHasKey('email', $user->getErrors());
    }

    public function testEnforcesUniqueEmailRule(): void
    {
        $user = $this->Users->newEntity([
            'email' => 'admin@cabinet.local',
            'password' => 'secret123',
            'name' => 'Dup',
        ]);
        $saved = $this->Users->save($user);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('email', $user->getErrors());
    }

    public function testHashesPasswordOnSave(): void
    {
        $user = $this->Users->newEntity([
            'email' => 'fresh@cabinet.local',
            'password' => 'plain-secret-1',
            'name' => 'Fresh',
        ]);
        $saved = $this->Users->save($user);

        $this->assertNotFalse($saved);
        $this->assertNotSame('plain-secret-1', $saved->password);
        $this->assertNotNull($saved->password);
        $this->assertStringStartsWith('$2y$', $saved->password);
    }

    public function testChangesPasswordWhenCurrentIsCorrect(): void
    {
        $user = $this->userWithPassword('current-pass-1');

        $this->Users->changePassword($user, 'current-pass-1', 'brand-new-pass-2');

        $reloaded = $this->Users->get(1);
        $this->assertTrue(new DefaultPasswordHasher()->check('brand-new-pass-2', (string) $reloaded->password));
    }

    public function testChangePasswordRejectsIncorrectCurrentPassword(): void
    {
        $user = $this->userWithPassword('current-pass-1');

        $this->expectException(DomainException::class);
        $this->Users->changePassword($user, 'wrong-pass', 'brand-new-pass-2');
    }

    public function testChangePasswordRejectsTooShortNewPassword(): void
    {
        $user = $this->userWithPassword('current-pass-1');

        $this->expectException(DomainException::class);
        $this->Users->changePassword($user, 'current-pass-1', 'short');
    }

    public function testChangePasswordRejectsReusingTheCurrentPassword(): void
    {
        $user = $this->userWithPassword('current-pass-1');

        $this->expectException(DomainException::class);
        $this->Users->changePassword($user, 'current-pass-1', 'current-pass-1');
    }

    private function userWithPassword(string $plain): User
    {
        $user = $this->Users->get(1);
        $user->password = $plain;
        $this->Users->saveOrFail($user);

        return $this->Users->get(1);
    }
}
