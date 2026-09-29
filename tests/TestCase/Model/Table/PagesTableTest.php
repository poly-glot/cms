<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Page;
use App\Model\Enum\PostStatus;
use App\Model\Table\PagesTable;
use Cake\TestSuite\TestCase;

final class PagesTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Pages', 'app.Tags', 'app.Taggables', 'app.Media', 'app.Blocks', 'app.PageBlocks', 'app.ContentTypeFieldSchemas'];
    private PagesTable $Pages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Pages = $this->fetchTable('Pages');
    }

    public function testRequiresTitleAndSlug(): void
    {
        $page = $this->Pages->newEntity(['status' => 'draft', 'author_id' => 1]);

        $this->assertArrayHasKey('title', $page->getErrors());
        $this->assertArrayHasKey('slug', $page->getErrors());
    }

    public function testRejectsSlugWithSpacesOrUppercase(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'X',
            'slug' => 'Bad Slug',
            'status' => 'draft',
            'author_id' => 1,
        ]);

        $this->assertArrayHasKey('slug', $page->getErrors());
    }

    public function testRejectsReservedSlugs(): void
    {
        foreach (['admin', 'login', 'logout'] as $reserved) {
            $page = $this->Pages->newEntity([
                'title' => 'X',
                'slug' => $reserved,
                'status' => 'draft',
                'author_id' => 1,
            ]);

            $this->assertArrayHasKey('slug', $page->getErrors(), "Expected slug '$reserved' to be rejected");
        }
    }

    public function testRejectsInvalidStatus(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'X',
            'slug' => 'x',
            'status' => 'banana',
            'author_id' => 1,
        ]);

        $this->assertArrayHasKey('status', $page->getErrors());
    }

    public function testEnforcesUniqueSlugRule(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'Dup',
            'slug' => 'about',
            'status' => 'draft',
            'author_id' => 1,
        ]);
        $saved = $this->Pages->save($page);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('slug', $page->getErrors());
    }

    public function testStatusEntityAccessorReturnsEnum(): void
    {
        $page = $this->Pages->get(1);

        $this->assertSame(PostStatus::Live, $page->statusEnum);
    }

    public function testFindLiveReturnsOnlyLivePages(): void
    {
        /** @var array<int, Page> $rows */
        $rows = $this->Pages->find('live')->all()->toArray();

        $this->assertCount(2, $rows);
        $slugs = array_column($rows, 'slug');
        $this->assertContains('about', $slugs);
        $this->assertContains('press', $slugs);
    }

    public function testSlugUniquenessIsScopedToParent(): void
    {
        $first = $this->Pages->newEntity([
            'title' => 'About in another tree',
            'slug' => 'about',
            'status' => 'draft',
            'author_id' => 1,
            'parent_id' => 3,
        ]);
        $this->assertNotFalse($this->Pages->save($first));

        $dup = $this->Pages->newEntity([
            'title' => 'Same parent, same slug',
            'slug' => 'about',
            'status' => 'draft',
            'author_id' => 1,
            'parent_id' => 3,
        ]);
        $this->assertFalse($this->Pages->save($dup));
        $this->assertArrayHasKey('slug', $dup->getErrors());
    }

    public function testRejectsCycleInParentChain(): void
    {
        $page = $this->Pages->get(1);
        $page->parent_id = 3;

        $saved = $this->Pages->save($page);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('parent_id', $page->getErrors());
    }

    public function testRejectsSelfAsParent(): void
    {
        $page = $this->Pages->get(1);
        $page->parent_id = 1;

        $saved = $this->Pages->save($page);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('parent_id', $page->getErrors());
    }

    public function testRejectsScheduledWithoutPublishedAt(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'Scheduled',
            'slug' => 'scheduled',
            'status' => 'scheduled',
            'author_id' => 1,
        ]);

        $this->assertFalse($this->Pages->save($page));
        $this->assertArrayHasKey('published_at', $page->getErrors());
    }

    public function testRejectsScheduledWithPastPublishedAt(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'Past schedule',
            'slug' => 'past-schedule',
            'status' => 'scheduled',
            'published_at' => '2020-01-01 00:00:00',
            'author_id' => 1,
        ]);

        $this->assertFalse($this->Pages->save($page));
        $this->assertArrayHasKey('published_at', $page->getErrors());
    }

    public function testRejectsInvalidTemplate(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'X',
            'slug' => 'x',
            'status' => 'draft',
            'template' => 'banana',
            'author_id' => 1,
        ]);

        $this->assertArrayHasKey('template', $page->getErrors());
    }

    public function testRejectsVisibilityOtherThanPublic(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'X',
            'slug' => 'x',
            'status' => 'draft',
            'visibility' => 'private',
            'author_id' => 1,
        ]);

        $this->assertArrayHasKey('visibility', $page->getErrors());
    }

    public function testFindPublicByPathResolvesRoot(): void
    {
        $page = $this->Pages->findPublicByPath('about');

        $this->assertNotNull($page);
        $this->assertSame(1, $page->id);
    }

    public function testFindPublicByPathResolvesChild(): void
    {
        $page = $this->Pages->findPublicByPath('about/press');

        $this->assertNotNull($page);
        $this->assertSame(3, $page->id);
    }

    public function testFindPublicByPathReturnsNullForDraft(): void
    {
        $this->assertNull($this->Pages->findPublicByPath('draft-page'));
    }

    public function testFindPublicByPathReturnsNullForBrokenSegment(): void
    {
        $this->assertNull($this->Pages->findPublicByPath('about/nonexistent'));
    }

    public function testPathsByIdJoinsAncestorSlugs(): void
    {
        $paths = $this->Pages->pathsById();
        ksort($paths);

        $this->assertSame([1 => 'about', 2 => 'draft-page', 3 => 'about/press'], $paths);
    }

    public function testFindByStatusFiltersList(): void
    {
        $drafts = $this->Pages->find('byStatus', status: 'draft')->toArray();

        $this->assertCount(1, $drafts);
        $this->assertSame('draft-page', $drafts[0]->slug);
    }

    public function testFindByTagsJoinsThroughTaggables(): void
    {
        $rows = $this->Pages->find('byTags', slugs: ['heritage'])->toArray();

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]->id);
    }

    public function testAfterSaveIndexesBlockReferences(): void
    {
        $page = $this->Pages->get(2);
        $page->body = '<div data-block="1"></div>';

        $this->Pages->saveOrFail($page);

        $pageBlocks = $this->fetchTable('PageBlocks');
        $this->assertSame(1, $pageBlocks->find()->where(['page_id' => 2, 'block_id' => 1])->count());
    }

    public function testBeforeSaveSanitizesBodyFromPatch(): void
    {
        $page = $this->Pages->patchEntity($this->Pages->get(1), [
            'body' => '<p>safe copy</p><script>alert(document.cookie)</script>',
        ]);
        $this->Pages->saveOrFail($page);

        $reloaded = $this->Pages->get(1);
        $this->assertStringNotContainsString('<script', (string) $reloaded->body);
        $this->assertStringContainsString('safe copy', (string) $reloaded->body);
    }

    public function testBeforeSaveSanitizesDirectBodyAssignment(): void
    {
        $page = $this->Pages->get(1);
        $page->body = '<p>restored</p><img src="x" onerror="alert(1)">';
        $this->Pages->saveOrFail($page);

        $reloaded = $this->Pages->get(1);
        $this->assertStringNotContainsString('onerror', (string) $reloaded->body);
        $this->assertStringContainsString('restored', (string) $reloaded->body);
    }

    public function testRejectsNonArrayData(): void
    {
        $page = $this->Pages->newEntity([
            'title' => 'X', 'slug' => 'x', 'status' => 'draft', 'author_id' => 1,
            'data' => 'not-a-map',
        ]);

        $this->assertArrayHasKey('data', $page->getErrors());
    }

    public function testBeforeSaveSanitizesRichTextInData(): void
    {
        $schemas = $this->fetchTable('ContentTypeFieldSchemas');
        $schema = $schemas->newEmptyEntity();
        $schema->subject_type = 'Pages';
        $schema->field_schema = [['name' => 'sidebar', 'label' => 'Sidebar', 'type' => 'rich_text']];
        $schemas->saveOrFail($schema);

        $page = $this->Pages->patchEntity($this->Pages->get(1), [
            'data' => ['sidebar' => '<p>keep</p><script>alert(1)</script>'],
        ]);
        $this->Pages->saveOrFail($page);

        $sidebar = $this->Pages->get(1)->data['sidebar'];
        $this->assertIsString($sidebar);
        $this->assertStringNotContainsString('<script', $sidebar);
        $this->assertStringContainsString('keep', $sidebar);
    }

    public function testDataUsageByFieldCountsNonEmptyValues(): void
    {
        $first = $this->Pages->get(1);
        $first->data = ['subtitle' => 'Filled', 'blank' => ''];
        $this->Pages->saveOrFail($first, ['checkRules' => false]);

        $second = $this->Pages->get(2);
        $second->data = ['subtitle' => 'Also filled'];
        $this->Pages->saveOrFail($second, ['checkRules' => false]);

        $usage = $this->Pages->getBehavior('EditorialContent')->dataUsageByField();

        $this->assertSame(2, $usage['subtitle']);
        $this->assertArrayNotHasKey('blank', $usage);
    }
}
