<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class AppearanceControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Settings'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testNonAdminGetsForbidden(): void
    {
        $this->session(['Auth' => ['id' => 2, 'email' => 'eleanor@cabinet.local', 'name' => 'Eleanor', 'role' => 'editor']]);
        $this->get('/cabinet/admin/appearance');

        $this->assertResponseCode(403);
    }

    public function testIndexRendersThemeCardsWithActiveBadge(): void
    {
        $this->get('/cabinet/admin/appearance');

        $this->assertResponseOk();
        $this->assertResponseContains('Heritage');
        $this->assertResponseContains('Atelier Dark');
        $this->assertResponseContains('Paper');
        $this->assertResponseContains('· active');
    }

    public function testActivatePersistsTheSelectedTheme(): void
    {
        $this->post('/cabinet/admin/appearance/activate', ['theme' => 'paper']);

        $this->assertRedirect('/cabinet/admin/appearance');
        $this->assertSame('paper', $this->fetchTable('Settings')->value('theme'));
    }

    public function testActivateRejectsUnknownTheme(): void
    {
        $this->post('/cabinet/admin/appearance/activate', ['theme' => 'bogus']);

        $this->assertResponseCode(404);
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/appearance');

        $this->assertRedirectContains('/login');
    }
}
