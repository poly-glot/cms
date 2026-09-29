<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class TenancyRoutingTest extends TestCase
{
    use IntegrationTestTrait;

    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Pages', 'app.PageRevisions', 'app.Media', 'app.Blocks'];

    public function testUnknownWorkspaceSlugReturns404(): void
    {
        $this->get('/no-such-workspace/about');

        $this->assertResponseCode(404);
    }

    public function testAdminWithoutMembershipReturns403(): void
    {
        $this->session(['Auth' => ['id' => 99, 'email' => 'stranger@example.com', 'name' => 'Stranger', 'role' => 'admin']]);

        $this->get('/cabinet/admin/pages');

        $this->assertResponseCode(403);
    }

    public function testPublicResolverHonoursWorkspaceScope(): void
    {
        $atelierPageId = $this->createAtelierAboutPage();

        $this->get('/atelier/about');

        $this->assertResponseOk();
        $this->assertResponseContains('Atelier workshop notes.');
        $this->assertGreaterThan(0, $atelierPageId);
    }

    public function testHomeRedirectsAuthenticatedUserToFirstWorkspace(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);

        $this->get('/');

        $this->assertRedirectContains('/atelier/admin');
    }

    public function testHomeRedirectsAnonymousVisitorToLogin(): void
    {
        $this->get('/');

        $this->assertRedirect('/login');
    }

    private function createAtelierAboutPage(): int
    {
        $pages = $this->fetchTable('Pages');

        $entity = TenantContext::instance()->runScoped(2, UserRole::Admin, static function () use ($pages): EntityInterface {
            $page = $pages->newEntity([
                'title' => 'Atelier About',
                'slug' => 'about',
                'body' => '<p>Atelier workshop notes.</p>',
                'status' => 'live',
                'visibility' => 'public',
                'template' => 'default',
                'parent_id' => null,
                'position' => 0,
                'author_id' => 1,
            ]);

            return $pages->saveOrFail($page);
        }, workspaceSlug: 'atelier');

        return (int) $entity->id;
    }
}
