<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MediaTable;
use Cake\TestSuite\TestCase;

final class MediaTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Media'];
    private MediaTable $Media;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Media = $this->fetchTable('Media');
    }

    public function testEnforcesUniqueFilenameRule(): void
    {
        $media = $this->Media->newEmptyEntity();
        $media->name = 'dup.jpg';
        $media->filename = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $media->mime = 'image/jpeg';
        $media->size = 100;
        $media->uploaded_by = 1;

        $saved = $this->Media->save($media);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('filename', $media->getErrors());
    }

    public function testAltIsAccessibleButFilenameIsNot(): void
    {
        $media = $this->Media->get(1);
        $media = $this->Media->patchEntity($media, [
            'alt' => 'Updated alt',
            'filename' => 'attempt-to-overwrite',
        ]);

        $this->assertSame('Updated alt', $media->alt);
        $this->assertSame('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', $media->filename);
    }

    public function testFindOfKindFiltersByMimePrefix(): void
    {
        $this->makeMedia('report.pdf', 'application/pdf', 10);
        $this->makeMedia('clip.mp4', 'video/mp4', 11);

        $images = $this->Media->find('ofKind', kind: 'image')->all()->extract('name')->toList();
        $this->assertContains('workbench.jpg', $images);
        $this->assertNotContains('report.pdf', $images);

        $documents = $this->Media->find('ofKind', kind: 'document')->all()->extract('name')->toList();
        $this->assertContains('report.pdf', $documents);
        $this->assertNotContains('workbench.jpg', $documents);
        $this->assertNotContains('clip.mp4', $documents);
    }

    public function testFindSearchMatchesNameAndEscapesWildcards(): void
    {
        $this->makeMedia('annual-report.pdf', 'application/pdf', 12);

        $found = $this->Media->find('search', keyword: 'report')->all()->extract('name')->toList();
        $this->assertContains('annual-report.pdf', $found);
        $this->assertNotContains('workbench.jpg', $found);

        $this->assertCount(0, $this->Media->find('search', keyword: '%')->all()->toList());
    }

    private function makeMedia(string $name, string $mime, int $seed): void
    {
        $media = $this->Media->newEmptyEntity();
        $media->name = $name;
        $media->mime = $mime;
        $media->filename = sprintf('%08d-0000-0000-0000-000000000000', $seed);
        $media->size = 1234;
        $media->uploaded_by = 1;
        $this->Media->saveOrFail($media);
    }
}
