<?php

declare(strict_types=1);

namespace App\Test\TestCase\Middleware;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;

final class RateLimitMiddlewareTest extends TestCase
{
    use IntegrationTestTrait;

    /** Mirrors RateLimitMiddleware::DEFAULT_BURST. */
    private const int DEFAULT_BURST = 10;

    /** @var array<int, string> */
    protected array $fixtures = [
        'app.Users', 'app.Memberships', 'app.Pages', 'app.PageRevisions',
        'app.Tags', 'app.Taggables', 'app.Media', 'app.Blocks',
    ];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('RateLimit.enabled', true);
        Cache::clear('ratelimit');
    }

    #[Override]
    protected function tearDown(): void
    {
        Configure::write('RateLimit.enabled', false);
        Cache::clear('ratelimit');
        parent::tearDown();
    }

    public function testDefaultTierThrottlesAfterBurst(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin']]);

        // A few past the burst so token refill during the loop cannot rescue the last request.
        for ($i = 0; $i < self::DEFAULT_BURST + 5; ++$i) {
            $this->get('/cabinet/admin/pages');
        }

        $this->assertResponseCode(429);
        $this->assertHeader('Retry-After', '1');
    }

    public function testGenerousTierDoesNotThrottlePublicPages(): void
    {
        for ($i = 0; $i < self::DEFAULT_BURST + 5; ++$i) {
            $this->get('/cabinet/about');
            $this->assertResponseCode(200);
        }
    }

    public function testAdminAjaxIsExemptFromStrictTier(): void
    {
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin']]);
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);

        for ($i = 0; $i < self::DEFAULT_BURST + 10; ++$i) {
            $this->get('/cabinet/admin/tags');
            $this->assertResponseCode(200);
        }
    }

    public function testDisabledFlagBypassesLimiting(): void
    {
        Configure::write('RateLimit.enabled', false);
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin']]);

        for ($i = 0; $i <= self::DEFAULT_BURST + 3; ++$i) {
            $this->get('/cabinet/admin/pages');
            $this->assertResponseCode(200);
        }
    }
}
