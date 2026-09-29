<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\User;

use App\Exception\TenantContextException;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use App\Service\User\InvitationService;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\TestCase;
use DomainException;
use Override;

final class InvitationServiceTest extends TestCase
{
    use EmailTrait;

    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships'];

    #[Override]
    protected function getFixtureStrategy(): TransactionStrategy
    {
        return new TransactionStrategy();
    }

    public function testInviteCreatesPendingUserAndMembership(): void
    {
        $service = new InvitationService();

        $result = $service->invite(name: 'Pat', email: 'pat@example.com', role: 'author');

        $this->assertTrue($result['isNewUser']);
        $this->assertNotNull($result['token']);
        $this->assertSame(64, strlen((string) $result['token']));

        $user = $this->fetchTable('Users')
            ->find()->where(['email' => 'pat@example.com'])->firstOrFail();
        $this->assertNull($user->password);
        $this->assertSame(hash('sha256', (string) $result['token']), $user->invitation_token_hash);
        $this->assertNull($user->accepted_at);

        $memberships = $this->fetchTable('Memberships');
        $membership = $memberships->find()
            ->where(['workspace_id' => 1, 'user_id' => $user->id])
            ->firstOrFail();
        $this->assertSame('author', $membership->role);
    }

    public function testInviteAddsExistingUserToWorkspaceWithoutToken(): void
    {
        $service = new InvitationService();

        $result = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            static fn (): array => $service->invite(
                name: 'Eleanor',
                email: 'eleanor@cabinet.local',
                role: 'editor',
            ),
            workspaceSlug: 'atelier',
        );

        $this->assertFalse($result['isNewUser']);
        $this->assertNull($result['token']);
        $this->assertSame(2, $result['user']->id);

        $memberships = $this->fetchTable('Memberships');
        $membership = $memberships->find()
            ->where(['workspace_id' => 2, 'user_id' => 2])
            ->firstOrFail();
        $this->assertSame('editor', $membership->role);
    }

    public function testInviteRejectsExistingMembershipInSameWorkspace(): void
    {
        $service = new InvitationService();

        $this->expectException(DomainException::class);
        $service->invite(name: 'Eleanor', email: 'eleanor@cabinet.local', role: 'editor');
    }

    public function testInviteRejectsInvalidEmail(): void
    {
        $service = new InvitationService();

        $this->expectException(PersistenceFailedException::class);
        $service->invite(name: 'Bad', email: 'not-an-email', role: 'author');
    }

    public function testInviteThrowsWhenNoActiveWorkspace(): void
    {
        TenantContext::instance()->clear();
        $service = new InvitationService();

        $this->expectException(TenantContextException::class);
        $service->invite(name: 'Noone', email: 'noone@example.com', role: 'author');
    }

    public function testAcceptSetsPasswordAndClearsToken(): void
    {
        $service = new InvitationService();
        ['token' => $token] = $service->invite('Pat', 'pat@example.com', 'author');

        $user = $service->accept(token: (string) $token, password: 'super-secret-1');

        $this->assertNotNull($user->password);
        $this->assertStringStartsWith('$2y$', (string) $user->password);
        $this->assertNull($user->invitation_token_hash);
        $this->assertNull($user->invitation_expires_at);
        $this->assertNotNull($user->accepted_at);
    }

    public function testAcceptRejectsShortPassword(): void
    {
        $service = new InvitationService();
        ['token' => $token] = $service->invite('Pat', 'pat@example.com', 'author');

        $this->expectException(DomainException::class);
        $service->accept(token: (string) $token, password: 'short');
    }

    public function testAcceptRejectsUnknownToken(): void
    {
        $service = new InvitationService();

        $this->expectException(DomainException::class);
        $service->accept(token: str_repeat('a', 64), password: 'super-secret-1');
    }

    public function testAcceptRejectsReusedToken(): void
    {
        $service = new InvitationService();
        ['token' => $token] = $service->invite('Pat', 'pat@example.com', 'author');
        $service->accept((string) $token, 'super-secret-1');

        $this->expectException(DomainException::class);
        $service->accept((string) $token, 'super-secret-2');
    }

    public function testAcceptRejectsExpiredToken(): void
    {
        $service = new InvitationService();
        ['token' => $token] = $service->invite('Pat', 'pat@example.com', 'author');

        $users = $this->fetchTable('Users');
        $user = $users->find()->where(['email' => 'pat@example.com'])->firstOrFail();
        $user->invitation_expires_at = new DateTime('2020-01-01 00:00:00');
        $users->saveOrFail($user);

        $this->expectException(DomainException::class);
        $service->accept((string) $token, 'super-secret-1');
    }

    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow();
        parent::tearDown();
    }
}
