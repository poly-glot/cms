<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class NavigationControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Pages', 'app.Menus', 'app.MenuItems'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexRendersBuilderWithEmbeddedData(): void
    {
        $this->get('/cabinet/admin/navigation');

        $this->assertResponseOk();
        $this->assertResponseContains('data-nav-tree');
        $this->assertResponseContains('data-menu-id="main"');
        $this->assertResponseContains('data-nav-data');
        // The embedded JSON carries the existing menu item labels.
        $this->assertResponseContains('Shop');
    }

    public function testSaveRebuildsActiveMenu(): void
    {
        $tree = json_encode([
            ['type' => 'url', 'label' => 'Journal', 'url' => '/journal', 'target' => '_self', 'children' => [
                ['type' => 'page', 'label' => 'About', 'pageId' => 1, 'target' => '_self', 'children' => []],
            ]],
        ]);
        $this->post('/cabinet/admin/navigation/save', ['menu' => 'main', 'tree' => $tree]);

        $this->assertResponseOk();
        $menuItems = $this->fetchTable('MenuItems');
        $this->assertSame(2, $menuItems->find()->where(['menu_id' => 1])->count());
        $this->assertTrue($menuItems->exists(['title' => 'Journal', 'menu_id' => 1]));
        $this->assertFalse($menuItems->exists(['title' => 'Shop', 'menu_id' => 1]));
    }

    public function testSaveRejectsUnknownMenu(): void
    {
        $this->post('/cabinet/admin/navigation/save', ['menu' => 'ghost', 'tree' => '[]']);

        $this->assertResponseCode(404);
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/navigation');

        $this->assertRedirectContains('/login');
    }
}
