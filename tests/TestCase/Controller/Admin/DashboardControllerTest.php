<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class DashboardControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Media', 'app.Blocks'];

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->get('/cabinet/admin');

        $this->assertRedirectContains('/login');
    }

    public function testAuthenticatedSeesCreateHubAndBlockLibrary(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
        $this->get('/cabinet/admin');

        $this->assertResponseOk();
        $this->assertResponseContains('Start something new');
        $this->assertResponseContains('cms-create__tile');
        $this->assertResponseContains('/cabinet/admin/pages/add');
        $this->assertResponseContains('/cabinet/admin/blocks/add');
        $this->assertResponseContains('Your blocks');
        // Seeded/fixture block names render in the library cards.
        $this->assertResponseContains('Hours note');
    }
}
