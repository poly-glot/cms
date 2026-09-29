<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Tenancy\TenantContext;
use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;

final class SignupControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    #[Override]
    protected function getFixtureStrategy(): TransactionStrategy
    {
        return new TransactionStrategy();
    }

    public function testGetRendersForm(): void
    {
        $this->get('/signup');

        $this->assertResponseOk();
        $this->assertResponseContains('Create your workspace');
    }

    public function testGetRendersWithoutActiveWorkspace(): void
    {
        TenantContext::instance()->clear();

        $this->get('/signup');

        $this->assertResponseOk();
        $this->assertResponseContains('Create your workspace');
    }

    public function testSignupCreatesUserWorkspaceAndAdminMembership(): void
    {
        $this->post('/signup', [
            'name' => 'Maria Hale',
            'email' => 'maria@studio-north.test',
            'password' => 'twelve-char-pw',
            'workspace_name' => 'Studio North',
            'workspace_slug' => 'studio-north',
        ]);

        $this->assertRedirect('/studio-north/admin');

        $users = $this->fetchTable('Users');
        $user = $users->find()->where(['email' => 'maria@studio-north.test'])->first();
        $this->assertNotNull($user);

        $workspaces = $this->fetchTable('Workspaces');
        $workspace = $workspaces->find('bySlug', slug: 'studio-north')->first();
        $this->assertNotNull($workspace);
        $this->assertSame('Studio North', $workspace->name);

        $memberships = $this->fetchTable('Memberships');
        $membership = $memberships->find()
            ->where(['user_id' => $user->id, 'workspace_id' => $workspace->id])
            ->first();
        $this->assertNotNull($membership);
        $this->assertSame('admin', $membership->role);
    }

    public function testSignupRejectsDuplicateEmail(): void
    {
        $this->post('/signup', [
            'name' => 'Clone',
            'email' => 'admin@cabinet.local',
            'password' => 'twelve-char-pw',
            'workspace_name' => 'Clone Studio',
            'workspace_slug' => 'clone-studio',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Please correct');
        $this->assertNoWorkspaceCalled('clone-studio');
    }

    public function testSignupRejectsReservedSlug(): void
    {
        $this->post('/signup', [
            'name' => 'Wannabe',
            'email' => 'wannabe@example.com',
            'password' => 'twelve-char-pw',
            'workspace_name' => 'Wannabe Admin',
            'workspace_slug' => 'admin',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('reserved');
        $this->assertNoUserCalled('wannabe@example.com');
    }

    public function testSignupRejectsInvalidSlugShape(): void
    {
        $this->post('/signup', [
            'name' => 'Bad Slug',
            'email' => 'bad-slug@example.com',
            'password' => 'twelve-char-pw',
            'workspace_name' => 'Bad Slug',
            'workspace_slug' => 'Bad Slug!',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('lowercase letters');
        $this->assertNoUserCalled('bad-slug@example.com');
    }

    public function testSignupRejectsShortPassword(): void
    {
        $this->post('/signup', [
            'name' => 'Shorty',
            'email' => 'shorty@example.com',
            'password' => 'short',
            'workspace_name' => 'Shorty Studio',
            'workspace_slug' => 'shorty-studio',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Password must be at least 8 characters.');
        $this->assertNoUserCalled('shorty@example.com');
        $this->assertNoWorkspaceCalled('shorty-studio');
    }

    public function testAuthenticatedUserBouncesToHome(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);

        $this->get('/signup');

        $this->assertRedirect('/');
    }

    private function assertNoUserCalled(string $email): void
    {
        $users = $this->fetchTable('Users');
        $this->assertNull($users->find()->where(['email' => $email])->first(), sprintf('User %s should not exist.', $email));
    }

    private function assertNoWorkspaceCalled(string $slug): void
    {
        $workspaces = $this->fetchTable('Workspaces');
        $this->assertNull($workspaces->find('bySlug', slug: $slug)->first(), sprintf('Workspace %s should not exist.', $slug));
    }
}
