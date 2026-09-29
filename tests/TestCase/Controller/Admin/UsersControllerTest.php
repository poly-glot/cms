<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class UsersControllerTest extends TestCase
{
    use EmailTrait;
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Settings'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexListsUsersWithRolesAndPermissions(): void
    {
        $this->get('/cabinet/admin/users');

        $this->assertResponseOk();
        $this->assertResponseContains('Eleanor Voss');
        $this->assertResponseContains('Administrator');
        $this->assertResponseContains('Cannot change billing'); // Editor permission summary
        $this->assertResponseContains('name="role"');
    }

    public function testEditRoleChangesMembershipRole(): void
    {
        $this->post('/cabinet/admin/users/edit-role/2', ['role' => 'author']);

        $this->assertRedirect('/cabinet/admin/users');
        $membership = $this->fetchTable('Memberships')->get(2);
        $this->assertSame('author', $membership->role);
    }

    public function testEditRoleRejectsUnknownRole(): void
    {
        $this->post('/cabinet/admin/users/edit-role/2', ['role' => 'wizard']);

        $this->assertRedirect('/cabinet/admin/users');
        $membership = $this->fetchTable('Memberships')->get(2);
        $this->assertSame('editor', $membership->role);
    }

    public function testEditRoleRejectsForeignMembership(): void
    {
        $this->post('/cabinet/admin/users/edit-role/4', ['role' => 'author']);

        $this->assertResponseCode(404);
        $membership = $this->fetchTable('Memberships')->get(4);
        $this->assertSame('admin', $membership->role);
    }

    public function testIndexExcludesForeignWorkspaceMembers(): void
    {
        $this->get('/cabinet/admin/users');

        $this->assertResponseOk();
        $this->assertResponseContains('Eleanor');
        $this->assertResponseNotContains('atelier@example.com');
    }

    public function testInviteCreatesAPendingUser(): void
    {
        $this->post('/cabinet/admin/users/invite', [
            'name' => 'New Teammate',
            'email' => 'new@cabinet.local',
            'role' => 'editor',
        ]);

        $this->assertRedirect('/cabinet/admin/users');
        $created = $this->fetchTable('Users')
            ->find()->where(['email' => 'new@cabinet.local'])->firstOrFail();
        $this->assertNull($created->password);
        $this->assertNotNull($created->invitation_token_hash);
        $this->assertNull($created->accepted_at);

        $membership = $this->fetchTable('Memberships')
            ->find()
            ->where(['workspace_id' => 1, 'user_id' => $created->id])
            ->firstOrFail();
        $this->assertSame('editor', $membership->role);
    }

    public function testInviteRejectsExistingMember(): void
    {
        $this->post('/cabinet/admin/users/invite', [
            'name' => 'Eleanor',
            'email' => 'eleanor@cabinet.local',
            'role' => 'editor',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('This user already belongs to the workspace.');
    }

    public function testInviteRejectsInvalidEmail(): void
    {
        $this->post('/cabinet/admin/users/invite', [
            'name' => 'Bad',
            'email' => 'not-an-email',
            'role' => 'author',
        ]);

        $this->assertResponseOk();
        $this->assertFalse(
            $this->fetchTable('Users')->exists(['name' => 'Bad']),
        );
    }

    public function testSendResetIssuesATokenForTheMember(): void
    {
        $this->post('/cabinet/admin/users/send-reset/2');

        $this->assertRedirect('/cabinet/admin/users');
        $user = $this->fetchTable('Users')->get(2);
        $this->assertNotNull($user->password_reset_token_hash);
        $this->assertNotNull($user->password_reset_expires_at);
    }

    public function testSendResetRejectsForeignMembership(): void
    {
        $this->post('/cabinet/admin/users/send-reset/4');

        $this->assertResponseCode(404);
    }

    public function testSendResetIsNotTriggeredByGet(): void
    {
        $this->get('/cabinet/admin/users/send-reset/2');

        $this->assertResponseCode(404);
        $this->assertNull($this->fetchTable('Users')->get(2)->password_reset_token_hash);
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/users');

        $this->assertRedirectContains('/login');
    }

    public function testNonAdminGetsForbidden(): void
    {
        $this->session(['Auth' => ['id' => 2, 'email' => 'eleanor@cabinet.local', 'name' => 'Eleanor', 'role' => 'editor']]);
        $this->get('/cabinet/admin/users');

        $this->assertResponseCode(403);
    }
}
