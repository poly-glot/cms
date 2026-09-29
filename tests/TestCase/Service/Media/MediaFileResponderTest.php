<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Media;

use App\Model\Entity\Media;
use App\Service\Media\MediaFileResponder;
use App\Test\DeletesDirectories;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

final class MediaFileResponderTest extends TestCase
{
    use DeletesDirectories;

    private string $root;
    private string $privateDir;
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/cabinet-responder-' . uniqid();
        $this->privateDir = $this->root . '/private/2026/01/uuid-1';
        $this->publicDir = $this->root . '/public/2026/01/uuid-1';
        mkdir($this->privateDir, 0o755, true);
        mkdir($this->publicDir, 0o755, true);
        Configure::write('App.uploads.path', $this->root);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
        parent::tearDown();
    }

    private function media(string $name = 'photo.png'): Media
    {
        $media = new Media(['name' => $name]);
        $media->filename = 'uuid-1';
        $media->mime = 'image/png';
        $media->created = new DateTime('2026-01-15 10:00:00');

        return $media;
    }

    public function testPathReturnsRenditionWhenPresent(): void
    {
        touch($this->privateDir . '/photo.png');
        touch($this->publicDir . '/photo-thumb.png');

        $path = new MediaFileResponder()->path($this->media(), 'thumb');

        $this->assertSame($this->publicDir . '/photo-thumb.png', $path);
    }

    public function testPathFallsBackToOriginalWhenRenditionMissing(): void
    {
        touch($this->privateDir . '/photo.png');

        $path = new MediaFileResponder()->path($this->media(), 'large');

        $this->assertSame($this->privateDir . '/photo.png', $path);
    }

    public function testPathIgnoresUnknownRenditionName(): void
    {
        $path = new MediaFileResponder()->path($this->media(), 'huge');

        $this->assertSame($this->privateDir . '/photo.png', $path);
    }

    public function testPurgeRemovesBothMediaDirectories(): void
    {
        touch($this->privateDir . '/photo.png');
        touch($this->publicDir . '/photo-thumb.png');

        new MediaFileResponder()->purge($this->media());

        $this->assertDirectoryDoesNotExist($this->privateDir);
        $this->assertDirectoryDoesNotExist($this->publicDir);
    }

    public function testPurgeIsNoopWhenDirectoryMissing(): void
    {
        $media = $this->media();
        $media->filename = 'uuid-does-not-exist';

        new MediaFileResponder()->purge($media);

        $this->assertDirectoryExists($this->privateDir);
    }

    public function testPublicRenditionPathBuildsRelativeUrl(): void
    {
        $this->assertSame(
            '2026/01/uuid-1/photo-large.png',
            new MediaFileResponder()->publicRenditionPath($this->media(), 'large'),
        );
    }

    public function testRespondRefusesPathTraversalOutsideRoot(): void
    {
        $media = $this->media('../../../../../../etc/hostname');

        $this->expectException(NotFoundException::class);
        new MediaFileResponder()->respond($media, new Response());
    }
}
