<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Service\Media\UploadService;
use App\Test\DeletesDirectories;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

final class MediaControllerTest extends TestCase
{
    use DeletesDirectories;
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Media', 'app.MediaRenditions'];
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->storageRoot = sys_get_temp_dir() . '/cabinet-media-test-' . uniqid();
        mkdir($this->storageRoot, 0o755, true);
        Configure::write('App.uploads.path', $this->storageRoot);
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->storageRoot);
        parent::tearDown();
    }

    public function testIndexShowsExistingMedia(): void
    {
        $this->get('/cabinet/admin/media');

        $this->assertResponseOk();
        $this->assertResponseContains('workbench.jpg');
    }

    public function testUploadAcceptsValidPng(): void
    {
        $this->configRequest([
            'files' => ['file' => $this->uploadedSample('sample.png', 'image/png')],
        ]);
        $this->post('/cabinet/admin/media/upload', []);

        $this->assertResponseOk();
        $response = $this->_response;
        $this->assertNotNull($response);
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertSame('sample.png', $body['name']);
        $this->assertSame('image', $body['kind']);
        $this->assertTrue($body['isImage']);
        $this->assertIsString($body['thumbUrl']);
        $this->assertStringContainsString('/cabinet/admin/media/serve/', $body['thumbUrl']);
    }

    public function testDetailReturnsMetadataAndRenditions(): void
    {
        $this->get('/cabinet/admin/media/detail/1');

        $this->assertResponseOk();
        $body = json_decode((string) $this->_response?->getBody(), true);
        $this->assertIsArray($body);
        $this->assertSame('workbench.jpg', $body['name']);
        $this->assertSame('image', $body['kind']);
        $renditions = $body['renditions'];
        $this->assertIsArray($renditions);
        $this->assertCount(2, $renditions);
        $first = $renditions[0];
        $this->assertIsArray($first);
        $this->assertSame('large', $first['name']);
    }

    public function testLibraryReturnsPaginatedEnvelope(): void
    {
        $this->get('/cabinet/admin/media/library');

        $this->assertResponseOk();
        $body = json_decode((string) $this->_response?->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('items', $body);
        $this->assertArrayHasKey('hasMore', $body);
        $this->assertArrayHasKey('total', $body);

        $items = $body['items'];
        $this->assertIsArray($items);
        $first = $items[0];
        $this->assertIsArray($first);
        $this->assertSame('workbench.jpg', $first['name']);
        $this->assertIsString($first['thumbUrl']);
        $this->assertStringContainsString('rendition=thumb', $first['thumbUrl']);
        $this->assertSame(
            '/cabinet/media/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa?rendition=large',
            $first['publicUrl'],
        );
    }

    public function testIndexFiltersByKind(): void
    {
        $this->seedMedia(1, 'application/pdf', 'manual');

        $this->get('/cabinet/admin/media?kind=document');
        $this->assertResponseOk();
        $this->assertResponseContains('manual-1');
        $this->assertResponseNotContains('workbench.jpg');

        $this->get('/cabinet/admin/media?kind=image');
        $this->assertResponseContains('workbench.jpg');
        $this->assertResponseNotContains('manual-1');
    }

    public function testIndexSearchNarrowsAndShowsEmptyState(): void
    {
        $this->get('/cabinet/admin/media?q=workbench');
        $this->assertResponseOk();
        $this->assertResponseContains('workbench.jpg');

        $this->get('/cabinet/admin/media?q=zzzznope');
        $this->assertResponseOk();
        $this->assertResponseNotContains('workbench.jpg');
        $this->assertResponseContains('No files match');
    }

    public function testIndexPaginatesPastTheOldestFile(): void
    {
        $this->seedMedia(50, 'image/png', 'bulk');

        $this->get('/cabinet/admin/media');
        $this->assertResponseOk();
        $this->assertResponseContains('cms-pagination');
        $this->assertResponseNotContains('workbench.jpg');

        $this->get('/cabinet/admin/media?page=2');
        $this->assertResponseOk();
        $this->assertResponseContains('workbench.jpg');
    }

    public function testLibraryPaginatesAndFiltersByKind(): void
    {
        $this->seedMedia(30, 'image/png', 'pic');

        $this->get('/cabinet/admin/media/library?kind=image&page=1');
        $this->assertResponseOk();
        $body = json_decode((string) $this->_response?->getBody(), true);
        $this->assertIsArray($body);
        $this->assertTrue($body['hasMore']);
        $items = $body['items'];
        $this->assertIsArray($items);
        $this->assertCount(24, $items);
    }

    private function seedMedia(int $count, string $mime, string $prefix): void
    {
        $media = $this->fetchTable('Media');
        for ($i = 1; $i <= $count; ++$i) {
            $row = $media->newEmptyEntity();
            $row->name = $prefix . '-' . $i;
            $row->mime = $mime;
            $row->filename = sprintf('%s%05d-0000-0000-0000-000000000000', substr($prefix, 0, 3), $i);
            $row->size = 1234;
            $row->uploaded_by = 1;
            $media->saveOrFail($row);
        }
    }

    public function testServeStreamsUploadedImageAndRendition(): void
    {
        // Regression: the configured uploads root contains `..`
        // (WWW_ROOT/../storage), which Response::withFile() rejects unless the
        // path is canonicalised. Seed a real file, then serve original + thumb.
        $media = new UploadService()->store($this->uploadedSample('sample.png', 'image/png'), uploaderId: 1);

        $this->get('/cabinet/admin/media/serve/' . $media->id);
        $this->assertResponseOk();
        $this->assertContentType('image/png');

        $this->get('/cabinet/admin/media/serve/' . $media->id . '?rendition=thumb');
        $this->assertResponseOk();
    }

    public function testUpdateSavesAltText(): void
    {
        $this->post('/cabinet/admin/media/update/1', ['alt' => 'Updated description']);

        $this->assertResponseOk();
        $media = $this->fetchTable('Media')->get(1);
        $this->assertSame('Updated description', $media->alt);
    }

    public function testUploadRejectsBadMime(): void
    {
        $this->configRequest(['files' => ['file' => $this->uploadedSample('sample.jpg', 'image/jpeg')]]);
        $this->post('/cabinet/admin/media/upload', []);

        $this->assertResponseCode(422);
    }

    public function testDeleteRemovesRow(): void
    {
        $this->post('/cabinet/admin/media/delete/1');

        $this->assertRedirect('/cabinet/admin/media');
    }

    private function uploadedSample(string $clientName, string $clientMime): UploadedFile
    {
        $copy = $this->storageRoot . '/upload-' . uniqid() . '.png';
        copy(TESTS . 'Fixture/files/sample.png', $copy);

        return new UploadedFile($copy, (int) filesize($copy), \UPLOAD_ERR_OK, $clientName, $clientMime);
    }
}
