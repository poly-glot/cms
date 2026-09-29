<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class ApiPlaygroundControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships'];

    public function testAdminSeesThePlayground(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local']]);

        $this->get('/cabinet/admin/api-playground');

        $this->assertResponseOk();
        $this->assertResponseContains('GraphQL playground');
        $this->assertResponseContains('data-graphiql');
    }

    public function testNonAdminIsForbidden(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'marcus@cabinet.local']]);

        $this->get('/cabinet/admin/api-playground');

        $this->assertResponseCode(403);
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->get('/cabinet/admin/api-playground');

        $this->assertRedirectContains('/login');
    }
}
