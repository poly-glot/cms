<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Content;

use App\Exception\UnknownWorkspaceException;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use App\Service\Content\ContentImporter;
use App\Service\Content\ContentImportOutcome;
use App\Service\Content\ContentKind;
use App\Test\DeletesDirectories;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;

final class ContentImporterTest extends TestCase
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

    private ContentImporter $importer;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new ContentImporter();
        $this->root = TMP . 'tests' . DS . 'content-import-' . uniqid();
        mkdir($this->root, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
        parent::tearDown();
    }

    public function testCreatesPostFromFile(): void
    {
        $this->writeFile('cabinet/posts/welcome.yml', $this->postYaml('welcome', 'Welcome'));

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $this->assertCount(1, $results);
        $this->assertSame(ContentImportOutcome::Created, $results[0]->outcome);

        $post = $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'welcome'])->firstOrFail();
        $this->assertSame('Welcome', $post->title);
        $this->assertSame(1, $post->author_id);
        $this->assertNotNull($this->fetchTable('ContentImports')->hashFor('Posts', 'welcome'));
    }

    public function testReplacesPostWhenFileChanges(): void
    {
        $this->writeFile('cabinet/posts/welcome.yml', $this->postYaml('welcome', 'Welcome'));
        $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $this->writeFile('cabinet/posts/welcome.yml', $this->postYaml('welcome', 'Welcome Home'));
        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $this->assertSame(ContentImportOutcome::Updated, $results[0]->outcome);
        $this->assertSame(1, $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'welcome'])->count());
        $this->assertSame('Welcome Home', $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'welcome'])->firstOrFail()->title);
    }

    public function testSkipsUnchangedFile(): void
    {
        $this->writeFile('cabinet/posts/welcome.yml', $this->postYaml('welcome', 'Welcome'));
        $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $this->assertSame(ContentImportOutcome::Skipped, $results[0]->outcome);
    }

    public function testForceReimportsUnchangedFile(): void
    {
        $this->writeFile('cabinet/posts/welcome.yml', $this->postYaml('welcome', 'Welcome'));
        $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Posts], force: true);

        $this->assertSame(ContentImportOutcome::Updated, $results[0]->outcome);
    }

    public function testUnknownAuthorFailsWithoutWritingRowOrHash(): void
    {
        $this->writeFile('cabinet/posts/orphan.yml', $this->postYaml('orphan', 'Orphan', author: 'ghost@nowhere.test'));

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $this->assertSame(ContentImportOutcome::Failed, $results[0]->outcome);
        $this->assertStringContainsString('Unknown author email', (string) $results[0]->error);
        $this->assertNull($this->fetchTable('Posts')->find()->where(['Posts.slug' => 'orphan'])->first());
        $this->assertNull($this->fetchTable('ContentImports')->hashFor('Posts', 'orphan'));
    }

    public function testUnknownParentPageFails(): void
    {
        $this->writeFile('cabinet/pages/lonely.yml', $this->pageYaml('lonely', 'Lonely', parent: 'no-such-parent'));

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Pages]);

        $this->assertSame(ContentImportOutcome::Failed, $results[0]->outcome);
        $this->assertStringContainsString('Unknown parent page', (string) $results[0]->error);
        $this->assertNull($this->fetchTable('Pages')->find()->where(['Pages.slug' => 'lonely'])->first());
    }

    public function testResolvesParentPathToParentId(): void
    {
        $this->writeFile('cabinet/pages/about/careers.yml', $this->pageYaml('careers', 'Careers', parent: 'about'));

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Pages]);

        $this->assertSame(ContentImportOutcome::Created, $results[0]->outcome);
        $child = $this->fetchTable('Pages')->find()->where(['Pages.slug' => 'careers'])->firstOrFail();
        $this->assertSame(1, $child->parent_id);
    }

    public function testAttachesTagsBySlugCreatingMissing(): void
    {
        $this->writeFile('cabinet/posts/tagged.yml', $this->postYaml('tagged', 'Tagged', tags: ['heritage', 'fresh-ideas']));

        $this->importer->import($this->root, 'cabinet', [ContentKind::Posts]);

        $post = $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'tagged'])->firstOrFail();
        $joined = $this->fetchTable('Taggables')->find()
            ->where(['taggable_type' => 'Posts', 'taggable_id' => $post->id])
            ->count();

        $this->assertSame(2, $joined);
        $this->assertNotNull($this->fetchTable('Tags')->find()->where(['slug' => 'fresh-ideas'])->first());
    }

    public function testReconcilesEntryReferencesAcrossCollections(): void
    {
        $categoryId = $this->createCollection('category', [['name' => 'headline', 'label' => 'Headline', 'type' => 'text']]);
        $productId = $this->createCollection('product', [
            ['name' => 'price', 'label' => 'Price', 'type' => 'number'],
            ['name' => 'related', 'label' => 'Related', 'type' => 'reference', 'target' => 'category', 'cardinality' => 'many'],
        ]);
        $woodId = $this->createEntry($categoryId, 'wood', ['headline' => 'Wood']);

        $this->writeFile('cabinet/collections/product/oak-chair.yml', <<<YAML
            title: Oak Chair
            slug: oak-chair
            status: live
            published_at: null
            comments_enabled: false
            author: admin@cabinet.local
            references:
                related:
                    - wood
            data:
                price: 120
            YAML);

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Entries], collectionSlug: 'product');

        $this->assertSame(ContentImportOutcome::Created, $results[0]->outcome);

        $chair = $this->fetchTable('CollectionEntries')->find()->where(['collection_id' => $productId, 'CollectionEntries.slug' => 'oak-chair'])->firstOrFail();
        $edge = $this->fetchTable('CollectionEntryReferences')->find()
            ->where(['source_entry_id' => $chair->id])
            ->firstOrFail();

        $this->assertSame('related', $edge->field_name);
        $this->assertSame($woodId, $edge->target_entry_id);
    }

    public function testRejectsInvalidCustomDataAgainstSchema(): void
    {
        $this->writeFile('cabinet/collections/products/broken.yml', <<<YAML
            title: Broken
            slug: broken
            status: live
            published_at: null
            comments_enabled: false
            author: admin@cabinet.local
            references: []
            data:
                description: Missing its required price.
            YAML);

        $results = $this->importer->import($this->root, 'cabinet', [ContentKind::Entries], collectionSlug: 'products');

        $this->assertSame(ContentImportOutcome::Failed, $results[0]->outcome);
        $this->assertStringContainsString('price', (string) $results[0]->error);
        $this->assertNull($this->fetchTable('CollectionEntries')->find()->where(['CollectionEntries.slug' => 'broken'])->first());
    }

    public function testScopesImportToTargetWorkspace(): void
    {
        $this->writeFile('atelier/posts/scoped.yml', $this->postYaml('scoped', 'Scoped'));

        $results = $this->importer->import($this->root, 'atelier', [ContentKind::Posts]);

        $this->assertSame(ContentImportOutcome::Created, $results[0]->outcome);
        $this->assertNull($this->fetchTable('Posts')->find()->where(['Posts.slug' => 'scoped'])->first());

        $visibleInAtelier = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            fn (): int => $this->fetchTable('Posts')->find()->where(['Posts.slug' => 'scoped'])->count(),
            workspaceSlug: 'atelier',
        );
        $this->assertSame(1, $visibleInAtelier);
    }

    public function testCreatesCollectionFromSchemaDefinition(): void
    {
        $this->writeFile('schemas/product.yml', $this->productDefinition('Product'));

        $results = $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $this->assertCount(1, $results);
        $this->assertSame('product', $results[0]->identifier);
        $this->assertSame(ContentImportOutcome::Created, $results[0]->outcome);

        $collection = $this->fetchTable('Collections')->find()->where(['Collections.slug' => 'product'])->firstOrFail();
        $this->assertSame('Product', $collection->name);
        $this->assertCount(2, $collection->field_schema);
        $this->assertSame('price', $collection->field_schema[0]['name']);
        $this->assertNotNull($this->fetchTable('ContentImports')->hashFor('Collections', 'product'));
    }

    public function testUpdatesCollectionWhenSchemaDefinitionChanges(): void
    {
        $this->writeFile('schemas/product.yml', $this->productDefinition('Product'));
        $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $this->writeFile('schemas/product.yml', $this->productDefinition('Product Catalogue'));
        $results = $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $this->assertSame(ContentImportOutcome::Updated, $results[0]->outcome);
        $this->assertSame(1, $this->fetchTable('Collections')->find()->where(['Collections.slug' => 'product'])->count());
        $this->assertSame('Product Catalogue', $this->fetchTable('Collections')->find()->where(['Collections.slug' => 'product'])->firstOrFail()->name);
    }

    public function testSkipsUnchangedSchemaDefinition(): void
    {
        $this->writeFile('schemas/product.yml', $this->productDefinition('Product'));
        $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $results = $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $this->assertSame(ContentImportOutcome::Skipped, $results[0]->outcome);
    }

    public function testForceReimportsUnchangedSchemaDefinition(): void
    {
        $this->writeFile('schemas/product.yml', $this->productDefinition('Product'));
        $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $results = $this->importer->importSchemas($this->root . '/schemas', 'cabinet', force: true);

        $this->assertSame(ContentImportOutcome::Updated, $results[0]->outcome);
    }

    public function testRejectsInvalidSchemaDefinitionAndPersistsNothing(): void
    {
        $this->writeFile('schemas/broken.yml', "name: Broken\nslug: broken\nfields:\n  - {name: title, type: text}\n");

        $results = $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $this->assertSame(ContentImportOutcome::Failed, $results[0]->outcome);
        $this->assertStringContainsString('Reserved field name: title', (string) $results[0]->error);
        $this->assertNull($this->fetchTable('Collections')->find()->where(['Collections.slug' => 'broken'])->first());
        $this->assertNull($this->fetchTable('ContentImports')->hashFor('Collections', 'broken'));
    }

    public function testReportsSchemaDefinitionMissingSlug(): void
    {
        $this->writeFile('schemas/no-slug.yml', "name: No Slug\nfields:\n  - {name: a, type: text}\n");

        $results = $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $this->assertSame('no-slug', $results[0]->identifier);
        $this->assertSame(ContentImportOutcome::Failed, $results[0]->outcome);
    }

    public function testReportsMalformedSchemaYaml(): void
    {
        $this->writeFile('schemas/bad.yml', "name: [unterminated\n");

        $results = $this->importer->importSchemas($this->root . '/schemas', 'cabinet');

        $this->assertSame(ContentImportOutcome::Failed, $results[0]->outcome);
    }

    public function testScopesSchemaImportToTargetWorkspace(): void
    {
        $this->writeFile('schemas/product.yml', $this->productDefinition('Product'));

        $results = $this->importer->importSchemas($this->root . '/schemas', 'atelier');

        $this->assertSame(ContentImportOutcome::Created, $results[0]->outcome);
        $this->assertNull($this->fetchTable('Collections')->find()->where(['Collections.slug' => 'product'])->first());

        $productExistsInAtelier = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            fn (): bool => $this->fetchTable('Collections')->find()->where(['Collections.slug' => 'product'])->count() === 1,
            workspaceSlug: 'atelier',
        );
        $this->assertTrue($productExistsInAtelier);
    }

    public function testSchemaImportThrowsWhenWorkspaceIsUnknown(): void
    {
        $this->expectException(UnknownWorkspaceException::class);

        $this->importer->importSchemas($this->root . '/schemas', 'no-such-workspace');
    }

    /**
     * @param list<array<string, mixed>> $fields
     */
    private function createCollection(string $slug, array $fields): int
    {
        $collections = $this->fetchTable('Collections');
        $collection = $collections->newEntity([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'description' => null,
            'field_schema' => $fields,
        ]);

        return $collections->saveOrFail($collection)->id;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createEntry(int $collectionId, string $slug, array $data): int
    {
        $entries = $this->fetchTable('CollectionEntries');
        $entry = $entries->newEntity([
            'collection_id' => $collectionId,
            'title' => ucfirst($slug),
            'slug' => $slug,
            'status' => 'live',
            'data' => $data,
        ]);
        $entry->author_id = 1;

        return $entries->saveOrFail($entry)->id;
    }

    /**
     * @param list<string> $tags
     */
    private function postYaml(string $slug, string $title, string $author = 'admin@cabinet.local', array $tags = []): string
    {
        $tagBlock = $tags === []
            ? '[]'
            : "\n" . implode("\n", array_map(static fn (string $tag): string => '    - ' . $tag, $tags));

        return <<<YAML
            title: {$title}
            slug: {$slug}
            status: draft
            excerpt: null
            body: '<p>Hello.</p>'
            published_at: null
            comments_enabled: false
            author: {$author}
            tags: {$tagBlock}
            data: []
            YAML;
    }

    private function pageYaml(string $slug, string $title, ?string $parent = null): string
    {
        $parentValue = $parent ?? 'null';

        return <<<YAML
            title: {$title}
            slug: {$slug}
            status: draft
            body: '<p>Page body.</p>'
            template: default
            visibility: public
            published_at: null
            comments_enabled: false
            position: 0
            parent: {$parentValue}
            author: admin@cabinet.local
            tags: []
            data: []
            YAML;
    }

    private function productDefinition(string $name): string
    {
        return <<<YAML
            name: {$name}
            slug: product
            description: "Test product."
            fields:
              - name: price
                label: Price
                type: number
                required: true
              - name: specs
                label: Specs
                type: repeater
                fields:
                  - {name: key, type: text}
                  - {name: value, type: text}
            YAML;
    }

    private function writeFile(string $relativePath, string $yaml): void
    {
        $path = $this->root . '/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }
        file_put_contents($path, $yaml);
    }
}
