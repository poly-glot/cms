<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\Page;
use App\Model\Entity\Post;
use App\Model\Enum\ContentType;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AuthoredContentControllersTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Memberships',
        'app.Pages',
        'app.PageRevisions',
        'app.Posts',
        'app.Tags',
        'app.Taggables',
        'app.ContentTypeFieldSchemas',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin']]);
    }

    /**
     * @return array<string, array{ContentType, string}>
     */
    public static function contentTypes(): array
    {
        return [
            'pages' => [ContentType::Pages, '/cabinet/admin/pages'],
            'posts' => [ContentType::Posts, '/cabinet/admin/posts'],
        ];
    }

    /**
     * @return array<string, array{ContentType, string, int, int}>
     */
    public static function taggableContent(): array
    {
        return [
            'pages' => [ContentType::Pages, '/cabinet/admin/pages', 2, 3],
            'posts' => [ContentType::Posts, '/cabinet/admin/posts', 1, 2],
        ];
    }

    #[DataProvider('contentTypes')]
    public function testAddCoercesAndPersistsCustomData(ContentType $type, string $adminPath): void
    {
        $this->defineSchema($type, [
            ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'required' => false],
            ['name' => 'ranking', 'label' => 'Ranking', 'type' => 'number', 'required' => false],
        ]);

        $this->post($adminPath . '/add', [
            'title' => 'Craft',
            'slug' => 'craft',
            'body' => '<p>Body.</p>',
            'status' => 'draft',
            'data' => ['tagline' => 'We craft things', 'ranking' => '3.5'],
        ]);

        $saved = $this->findBySlug($type, 'craft');
        $this->assertRedirect($adminPath . '/edit/' . $saved->id);
        $this->assertSame('We craft things', $saved->data['tagline']);
        $this->assertSame(3.5, $saved->data['ranking']);
    }

    #[DataProvider('contentTypes')]
    public function testAddBlocksSaveWhenRequiredCustomFieldMissing(ContentType $type, string $adminPath): void
    {
        $this->defineSchema($type, [
            ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'required' => true],
        ]);

        $this->post($adminPath . '/add', [
            'title' => 'No tagline',
            'slug' => 'no-tagline',
            'body' => '<p>Body.</p>',
            'status' => 'draft',
            'data' => ['tagline' => ''],
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('This field is required.');
        $this->assertSame(0, $this->fetchTable($type->value)->find()->where(['slug' => 'no-tagline'])->count());
    }

    #[DataProvider('contentTypes')]
    public function testRichTextCustomFieldSanitizedServerSide(ContentType $type, string $adminPath): void
    {
        $this->defineSchema($type, [
            ['name' => 'blurb', 'label' => 'Blurb', 'type' => 'rich_text', 'required' => false],
        ]);

        $this->post($adminPath . '/add', [
            'title' => 'Rich',
            'slug' => 'rich',
            'body' => '<p>Body.</p>',
            'status' => 'draft',
            'data' => ['blurb' => '<p>Safe</p><script>alert(1)</script>'],
        ]);

        $blurb = $this->findBySlug($type, 'rich')->data['blurb'];
        $this->assertIsString($blurb);
        $this->assertStringContainsString('Safe', $blurb);
        $this->assertStringNotContainsString('<script', $blurb);
    }

    public function testPageSaveDraftCoercesCustomDataThroughPublisher(): void
    {
        $this->defineSchema(ContentType::Pages, [
            ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'required' => false],
        ]);

        $this->post('/cabinet/admin/pages/save-draft/1', [
            'title' => 'About Us',
            'slug' => 'about',
            'body' => '<p>Draft.</p>',
            'data' => ['tagline' => 'Since 1998'],
        ]);

        $this->assertRedirect('/cabinet/admin/pages/edit/1');
        $this->assertSame('Since 1998', $this->fetchTable('Pages')->get(1)->data['tagline']);
    }

    #[DataProvider('taggableContent')]
    public function testIndexFiltersByAllTags(ContentType $type, string $adminPath, int $bothTagsId, int $heritageOnlyId): void
    {
        $this->tag($type, $bothTagsId, 1);
        $this->tag($type, $bothTagsId, 2);
        $this->tag($type, $heritageOnlyId, 1);

        $this->get($adminPath . '?tags[]=heritage&tags[]=company&tag_mode=all');

        $this->assertResponseOk();
        $this->assertResponseContains($adminPath . '/edit/' . $bothTagsId . '"');
        $this->assertResponseNotContains($adminPath . '/edit/' . $heritageOnlyId . '"');
    }

    #[DataProvider('taggableContent')]
    public function testIndexFiltersByAnyTag(ContentType $type, string $adminPath, int $bothTagsId, int $heritageOnlyId): void
    {
        $this->tag($type, $bothTagsId, 1);
        $this->tag($type, $bothTagsId, 2);
        $this->tag($type, $heritageOnlyId, 1);

        $this->get($adminPath . '?tags[]=heritage&tags[]=company&tag_mode=any');

        $this->assertResponseOk();
        $this->assertResponseContains($adminPath . '/edit/' . $bothTagsId . '"');
        $this->assertResponseContains($adminPath . '/edit/' . $heritageOnlyId . '"');
    }

    private function findBySlug(ContentType $type, string $slug): Page|Post
    {
        $table = match ($type) {
            ContentType::Pages => $this->fetchTable('Pages'),
            ContentType::Posts => $this->fetchTable('Posts'),
        };

        return $table->find()->where(['slug' => $slug])->firstOrFail();
    }

    private function tag(ContentType $type, int $taggableId, int $tagId): void
    {
        $this->fetchTable('Taggables')->getConnection()->insert('taggables', [
            'workspace_id' => 1,
            'taggable_type' => $type->value,
            'taggable_id' => $taggableId,
            'tag_id' => $tagId,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $fields
     */
    private function defineSchema(ContentType $type, array $fields): void
    {
        $schemas = $this->fetchTable('ContentTypeFieldSchemas');
        $schema = $schemas->schemaFor($type);
        $schema = $schemas->patchEntity($schema, ['field_schema' => $fields, 'subject_type' => $type->value]);
        $schema->subject_type = $type->value;
        $schemas->saveOrFail($schema);
    }
}
