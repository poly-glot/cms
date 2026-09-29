<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\Fixture\TransactionStrategy;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;

final class WorkspacesControllerTest extends TestCase
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

    private function loginAsAdmin(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin']]);
    }

    public function testAddRedirectsAnonymousToLogin(): void
    {
        $this->get('/workspaces/new');

        $this->assertRedirectContains('/login');
    }

    public function testAddRendersFormWhenAuthenticated(): void
    {
        $this->loginAsAdmin();

        $this->get('/workspaces/new');

        $this->assertResponseOk();
        $this->assertResponseContains('Create a new workspace');
    }

    public function testAddCreatesWorkspaceAndOwnerMembership(): void
    {
        $this->loginAsAdmin();

        $this->post('/workspaces/new', ['name' => 'Glasshouse', 'slug' => 'glasshouse']);

        $this->assertRedirect('/glasshouse/admin');

        $workspaces = $this->fetchTable('Workspaces');
        $workspace = $workspaces->find('bySlug', slug: 'glasshouse')->first();
        $this->assertNotNull($workspace);
        $this->assertSame('Glasshouse', $workspace->name);

        $memberships = $this->fetchTable('Memberships');
        $membership = $memberships->find()
            ->where(['user_id' => 1, 'workspace_id' => $workspace->id])
            ->first();
        $this->assertNotNull($membership);
        $this->assertSame('admin', $membership->role);
    }

    public function testAddRejectsReservedSlug(): void
    {
        $this->loginAsAdmin();

        $this->post('/workspaces/new', ['name' => 'Admin Area', 'slug' => 'admin']);

        $this->assertResponseOk();
        $this->assertResponseContains('reserved');

        $workspaces = $this->fetchTable('Workspaces');
        $this->assertNull($workspaces->find('bySlug', slug: 'admin')->first());
    }

    public function testAddRejectsDuplicateSlug(): void
    {
        $this->loginAsAdmin();

        $this->post('/workspaces/new', ['name' => 'Cabinet Two', 'slug' => 'cabinet']);

        $this->assertResponseOk();
        $this->assertResponseContains('already in use');
    }
}
