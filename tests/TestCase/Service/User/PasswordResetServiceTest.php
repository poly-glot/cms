<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\User;

use App\Service\User\PasswordResetService;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use DomainException;

final class PasswordResetServiceTest extends TestCase
{
    use EmailTrait;

    protected array $fixtures = ['app.Users'];

    public function testRequestStoresHashAndSendsMail(): void
    {
        $service = new PasswordResetService();

        $service->request('admin@cabinet.local');

        $user = $this->fetchTable('Users')
            ->find()->where(['email' => 'admin@cabinet.local'])->firstOrFail();
        $this->assertNotNull($user->password_reset_token_hash);
        $this->assertSame(64, strlen((string) $user->password_reset_token_hash));
        $this->assertNotNull($user->password_reset_expires_at);
        $this->assertMailSentTo('admin@cabinet.local');
    }

    public function testRequestForUnknownEmailIsSilent(): void
    {
        $service = new PasswordResetService();

        $service->request('nobody@example.com');

        $unchanged = $this->fetchTable('Users')
            ->find()->where(['password_reset_token_hash IS NOT' => null])->count();
        $this->assertSame(0, $unchanged);
    }

    public function testRequestForAnInvitedUserWithoutAPasswordIsSilent(): void
    {
        $users = $this->fetchTable('Users');
        $users->updateAll(['password' => null], ['id' => 2]);

        new PasswordResetService()->request('eleanor@cabinet.local');

        $user = $users->get(2);
        $this->assertNull($user->password_reset_token_hash);
        $this->assertNull($user->password_reset_expires_at);
    }

    public function testResetSetsPasswordAndClearsToken(): void
    {
        $service = new PasswordResetService();
        $token = $this->issueToken('admin@cabinet.local');

        $user = $service->reset(token: $token, password: 'super-secret-1');

        $this->assertStringStartsWith('$2y$', (string) $user->password);
        $this->assertNull($user->password_reset_token_hash);
        $this->assertNull($user->password_reset_expires_at);
    }

    public function testResetRejectsShortPassword(): void
    {
        $service = new PasswordResetService();
        $token = $this->issueToken('admin@cabinet.local');

        $this->expectException(DomainException::class);
        $service->reset(token: $token, password: 'short');
    }

    public function testResetRejectsUnknownToken(): void
    {
        $service = new PasswordResetService();

        $this->expectException(DomainException::class);
        $service->reset(token: str_repeat('a', 64), password: 'super-secret-1');
    }

    public function testResetRejectsReusedToken(): void
    {
        $service = new PasswordResetService();
        $token = $this->issueToken('admin@cabinet.local');
        $service->reset($token, 'super-secret-1');

        $this->expectException(DomainException::class);
        $service->reset($token, 'super-secret-2');
    }

    public function testResetRejectsExpiredToken(): void
    {
        $service = new PasswordResetService();
        $token = $this->issueToken('admin@cabinet.local');

        $users = $this->fetchTable('Users');
        $user = $users->find()->where(['email' => 'admin@cabinet.local'])->firstOrFail();
        $user->password_reset_expires_at = new DateTime('2020-01-01 00:00:00');
        $users->saveOrFail($user);

        $this->expectException(DomainException::class);
        $service->reset($token, 'super-secret-1');
    }

    private function issueToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $users = $this->fetchTable('Users');
        $user = $users->find()->where(['email' => $email])->firstOrFail();
        $user->password_reset_token_hash = hash('sha256', $token);
        $user->password_reset_expires_at = DateTime::now()->addHours(1);
        $users->saveOrFail($user);

        return $token;
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
        parent::tearDown();
    }
}
