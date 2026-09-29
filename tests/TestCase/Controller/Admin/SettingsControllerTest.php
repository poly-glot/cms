<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class SettingsControllerTest extends TestCase
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
        $this->get('/cabinet/admin/settings');

        $this->assertResponseCode(403);
    }

    public function testIndexRendersFormWithDefaults(): void
    {
        $this->get('/cabinet/admin/settings');

        $this->assertResponseOk();
        $this->assertResponseContains('name="site_title"');
        $this->assertResponseContains('Moderation queue');
        $this->assertResponseContains('value="Cabinet"');
    }

    public function testSavePersistsGeneralAndToggles(): void
    {
        $this->post('/cabinet/admin/settings/save', [
            'site_title' => 'Atelier',
            'tagline' => 'Crafted slowly.',
            'allow_comments' => '1',
            'moderation_queue' => '0',
        ]);

        $this->assertRedirect('/cabinet/admin/settings');
        $settings = $this->fetchTable('Settings');
        $this->assertSame('Atelier', $settings->value('site_title'));
        $this->assertFalse($settings->isEnabled('moderation_queue'));
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/settings');

        $this->assertRedirectContains('/login');
    }
}
