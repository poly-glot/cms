<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class ErrorControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Pages'];

    public function testNotFoundRendersTheBrandedPageInsideTheErrorLayout(): void
    {
        $this->get('/cabinet/no-such-page');

        $this->assertResponseCode(404);
        $this->assertBrandedNotFoundPage();
    }

    public function testNotFoundRendersTheBrandedPageWithDebugOff(): void
    {
        Configure::write('debug', false);

        $this->get('/cabinet/no-such-page');

        $this->assertResponseCode(404);
        $this->assertBrandedNotFoundPage();
    }

    public function testAdminErrorsRenderTheAppErrorTemplate(): void
    {
        $this->session(['Auth' => ['id' => 2, 'email' => 'eleanor@cabinet.local', 'name' => 'Eleanor', 'role' => 'editor']]);

        $this->get('/cabinet/admin/appearance');

        $this->assertResponseCode(403);
        $this->assertBrandedNotFoundPage();
    }

    private function assertBrandedNotFoundPage(): void
    {
        $this->assertResponseContains('<title>Page not found — Cabinet</title>');
        $this->assertResponseContains('<main class="err">');
        $this->assertResponseContains('<p class="err__code">404</p>');
    }
}
