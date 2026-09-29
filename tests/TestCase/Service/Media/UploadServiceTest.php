<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Media;

use App\Exception\InvalidUploadException;
use App\Service\Media\UploadService;
use App\Test\DeletesDirectories;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

final class UploadServiceTest extends TestCase
{
    use DeletesDirectories;

    protected array $fixtures = ['app.Users', 'app.Media', 'app.MediaRenditions'];
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageRoot = sys_get_temp_dir() . '/cabinet-test-uploads-' . uniqid();
        mkdir($this->storageRoot, 0o755, true);
        Configure::write('App.uploads.path', $this->storageRoot);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->storageRoot);
        parent::tearDown();
    }

    private function uploadedFile(string $sourcePath, string $clientName, string $clientMime): UploadedFileInterface
    {
        $tmp = $this->storageRoot . '/upload-' . uniqid() . '.tmp';
        copy($sourcePath, $tmp);

        $size = filesize($tmp);

        return new UploadedFile($tmp, $size !== false ? $size : null, \UPLOAD_ERR_OK, $clientName, $clientMime);
    }

    public function testStoresValidPng(): void
    {
        $source = TESTS . 'Fixture/files/sample.png';
        $file = $this->uploadedFile($source, 'sample.png', 'image/png');
        $service = new UploadService();

        $media = $service->store($file, uploaderId: 1);

        $this->assertSame('sample.png', $media->name);
        $this->assertSame('image/png', $media->mime);
        $this->assertSame(1, $media->width);
        $this->assertSame(1, $media->height);
        $this->assertFileExists($this->storageRoot . '/private/' . date('Y/m') . '/' . $media->filename . '/sample.png');
        $this->assertDirectoryExists($this->storageRoot . '/public/' . date('Y/m') . '/' . $media->filename);
    }

    public function testRejectsMimeExtensionMismatch(): void
    {
        $source = TESTS . 'Fixture/files/sample.png';
        $file = $this->uploadedFile($source, 'sample.jpg', 'image/jpeg');
        $service = new UploadService();

        $this->expectException(InvalidUploadException::class);
        $service->store($file, uploaderId: 1);
    }

    public function testRejectsOversizedFile(): void
    {
        $source = TESTS . 'Fixture/files/sample.png';
        $tmp = $this->storageRoot . '/upload-' . uniqid() . '.tmp';
        copy($source, $tmp);
        $oversized = new UploadedFile($tmp, 50 * 1024 * 1024, \UPLOAD_ERR_OK, 'sample.png', 'image/png');
        $service = new UploadService();

        $this->expectException(InvalidUploadException::class);
        $service->store($oversized, uploaderId: 1);
    }
}
