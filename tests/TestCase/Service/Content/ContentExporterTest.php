<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Content;

use App\Service\Content\ContentExporter;
use App\Service\Content\ContentImporter;
use App\Service\Content\ContentKind;
use App\Test\DeletesDirectories;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ContentExporterTest extends TestCase
{
    use DeletesDirectories;
    use LocatorAwareTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Posts',
        'app.Pages',
        'app.Collections',
        'app.CollectionEntries',
        'app.CollectionEntryReferences',
        'app.Tags',
        'app.Taggables',
        'app.ContentImports',
        'app.ContentTypeFieldSchemas',
    ];

    private ContentExporter $exporter;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new ContentExporter();
        $this->root = TMP . 'tests' . DS . 'content-export-' . uniqid();
        mkdir($this->root, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
        parent::tearDown();
    }

    public function testExportsPostWithAuthorEmail(): void
    {
        $this->exporter->export($this->root, 'cabinet', [ContentKind::Posts]);

        $post = $this->readYaml('cabinet/posts/hello.yml');

        $this->assertSame('Hello from the workshop', $post['title']);
        $this->assertSame('hello', $post['slug']);
        $this->assertSame('live', $post['status']);
        $this->assertSame('A first journal entry.', $post['excerpt']);
        $this->assertSame('admin@cabinet.local', $post['author']);
        $this->assertSame('2026-01-10 09:00:00', $post['published_at']);
        $this->assertSame([], $post['tags']);
    }

    public function testExportsPageParentPathAndSortedTags(): void
    {
        $this->exporter->export($this->root, 'cabinet', [ContentKind::Pages]);

        $about = $this->readYaml('cabinet/pages/about.yml');
        $this->assertNull($about['parent']);
        $this->assertSame(['company', 'heritage'], $about['tags']);

        $press = $this->readYaml('cabinet/pages/about/press.yml');
        $this->assertSame('press', $press['slug']);
        $this->assertSame('about', $press['parent']);
        $this->assertSame('admin@cabinet.local', $press['author']);
    }

    public function testExportsEntryWithInlineData(): void
    {
        $this->exporter->export($this->root, 'cabinet', [ContentKind::Entries]);

        $entry = $this->readYaml('cabinet/collections/products/maple-chair.yml');
        $data = $entry['data'];
        $this->assertIsArray($data);

        $this->assertSame('Maple Chair', $entry['title']);
        $this->assertSame('admin@cabinet.local', $entry['author']);
        $this->assertSame(420, $data['price']);
        $this->assertSame('Hand-finished maple.', $data['description']);
        $this->assertSame([], $entry['references']);
    }

    public function testReExportIsByteStable(): void
    {
        $this->exporter->export($this->root, 'cabinet', [ContentKind::Posts]);
        $first = (string) file_get_contents($this->root . '/cabinet/posts/hello.yml');

        $this->exporter->export($this->root, 'cabinet', [ContentKind::Posts]);
        $second = (string) file_get_contents($this->root . '/cabinet/posts/hello.yml');

        $this->assertSame($first, $second);
    }

    public function testRoundTripYieldsEqualRecord(): void
    {
        $posts = $this->fetchTable('Posts');
        $original = $posts->find()->where(['Posts.slug' => 'hello'])->firstOrFail();

        $this->exporter->export($this->root, 'cabinet', [ContentKind::Posts]);
        $posts->delete($original);

        new ContentImporter()->import($this->root, 'cabinet', [ContentKind::Posts]);

        $reimported = $posts->find()->where(['Posts.slug' => 'hello'])->firstOrFail();
        $this->assertSame($original->title, $reimported->title);
        $this->assertSame($original->status, $reimported->status);
        $this->assertSame($original->excerpt, $reimported->excerpt);
        $this->assertSame($original->author_id, $reimported->author_id);
        $this->assertEquals($original->published_at, $reimported->published_at);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function readYaml(string $relativePath): array
    {
        $parsed = Yaml::parse((string) file_get_contents($this->root . '/' . $relativePath));
        $this->assertIsArray($parsed);

        return $parsed;
    }
}
