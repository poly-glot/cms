<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class TagsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Tags'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexReturnsAllTagsWithoutQuery(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/cabinet/admin/tags');

        $this->assertResponseOk();
        $this->assertNotNull($this->_response);
        $body = json_decode((string) $this->_response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertCount(2, $body);
    }

    public function testIndexFiltersByQueryPrefix(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/cabinet/admin/tags?q=her');

        $this->assertNotNull($this->_response);
        $body = json_decode((string) $this->_response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertCount(1, $body);
        $first = $body[0];
        $this->assertIsArray($first);
        $this->assertSame('heritage', $first['slug']);
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/cabinet/admin/tags');

        $this->assertRedirectContains('/login');
    }
}
