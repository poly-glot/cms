<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\DeletesDirectories;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class MediaControllerTest extends TestCase
{
    use DeletesDirectories;
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Media'];
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageRoot = sys_get_temp_dir() . '/cabinet-public-media-' . uniqid();
        Configure::write('App.uploads.path', $this->storageRoot);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->storageRoot);
        parent::tearDown();
    }

    public function testServesMediaByUuidWithoutAuthentication(): void
    {
        $dir = $this->storageRoot . '/private/2026/01/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        mkdir($dir, 0o755, true);
        file_put_contents($dir . '/workbench.jpg', 'fake-image-bytes');

        $this->get('/cabinet/media/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');

        $this->assertResponseOk();
        $this->assertContentType('image/jpeg');
    }

    public function testUnknownUuidReturns404(): void
    {
        $this->get('/cabinet/media/ffffffff-ffff-ffff-ffff-ffffffffffff');

        $this->assertResponseCode(404);
    }
}
