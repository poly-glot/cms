<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\Post;
use App\Model\Table\PostsTable;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

final class PostsTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Posts', 'app.ContentTypeFieldSchemas'];
    private PostsTable $Posts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Posts = $this->fetchTable('Posts');
    }

    public function testRequiresTitleSlugStatusAuthor(): void
    {
        $post = $this->Posts->newEntity([]);

        $this->assertArrayHasKey('title', $post->getErrors());
        $this->assertArrayHasKey('slug', $post->getErrors());
        $this->assertArrayHasKey('status', $post->getErrors());
        $this->assertArrayHasKey('author_id', $post->getErrors());
    }

    public function testRejectsInvalidSlug(): void
    {
        $post = $this->Posts->newEntity([
            'title' => 'X', 'slug' => 'Has Spaces', 'status' => 'draft', 'author_id' => 1,
        ]);

        $this->assertArrayHasKey('slug', $post->getErrors());
    }

    public function testEnforcesUniqueSlug(): void
    {
        $post = $this->Posts->newEntity([
            'title' => 'Dup', 'slug' => 'hello', 'status' => 'draft', 'author_id' => 1,
        ]);
        $saved = $this->Posts->save($post);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('slug', $post->getErrors());
    }

    public function testScheduledRequiresFuturePublishedAt(): void
    {
        $post = $this->Posts->newEntity([
            'title' => 'Schedule', 'slug' => 'schedule', 'status' => 'scheduled',
            'published_at' => new DateTime('2020-01-01 00:00:00'),
            'author_id' => 1,
        ]);
        $saved = $this->Posts->save($post);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('published_at', $post->getErrors());
    }

    public function testFindLiveReturnsOnlyLive(): void
    {
        $titles = array_map(
            static fn ($post): string => (string) $post->title,
            $this->Posts->find('live')->all()->toList(),
        );

        $this->assertSame(['Hello from the workshop'], $titles);
    }

    public function testFindBySlug(): void
    {
        $post = $this->Posts->findBySlug('hello')->first();

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame(1, $post->id);
    }

    public function testFindByAuthor(): void
    {
        $found = $this->Posts->find('byAuthor', authorId: 3)->all()->toList();

        $this->assertCount(1, $found);
        $this->assertSame('Draft thoughts', $found[0]->title);
    }

    public function testFindSearchMatchesTitleOrSlug(): void
    {
        $byTitle = $this->Posts->find('search', keyword: 'workshop')->all()->toList();
        $this->assertCount(1, $byTitle);
        $this->assertSame('Hello from the workshop', $byTitle[0]->title);

        $bySlug = $this->Posts->find('search', keyword: 'draft-thoughts')->all()->toList();
        $this->assertCount(1, $bySlug);
        $this->assertSame('Draft thoughts', $bySlug[0]->title);
    }

    public function testFindSearchTreatsWildcardsAsLiterals(): void
    {
        $this->assertCount(0, $this->Posts->find('search', keyword: '%')->all()->toList());
        $this->assertCount(0, $this->Posts->find('search', keyword: 'no-such-keyword')->all()->toList());
    }

    public function testRejectsNonArrayData(): void
    {
        $post = $this->Posts->newEntity([
            'title' => 'X', 'slug' => 'x', 'status' => 'draft', 'author_id' => 1,
            'data' => 'not-a-map',
        ]);

        $this->assertArrayHasKey('data', $post->getErrors());
    }

    public function testBeforeSaveSanitizesRichTextInData(): void
    {
        $schemas = $this->fetchTable('ContentTypeFieldSchemas');
        $schema = $schemas->newEmptyEntity();
        $schema->subject_type = 'Posts';
        $schema->field_schema = [['name' => 'note', 'label' => 'Note', 'type' => 'rich_text']];
        $schemas->saveOrFail($schema);

        $post = $this->Posts->patchEntity($this->Posts->get(1), [
            'data' => ['note' => '<p>Safe</p><script>alert(1)</script>'],
        ]);
        $this->Posts->saveOrFail($post);

        $note = $this->Posts->get(1)->data['note'];
        $this->assertIsString($note);
        $this->assertStringNotContainsString('<script', $note);
        $this->assertStringContainsString('Safe', $note);
    }

    public function testDataUsageByFieldCountsNonEmptyValues(): void
    {
        $first = $this->Posts->get(1);
        $first->data = ['reading_time' => 5, 'blank' => ''];
        $this->Posts->saveOrFail($first);

        $second = $this->Posts->get(2);
        $second->data = ['reading_time' => 8];
        $this->Posts->saveOrFail($second);

        $usage = $this->Posts->getBehavior('EditorialContent')->dataUsageByField();

        $this->assertSame(2, $usage['reading_time']);
        $this->assertArrayNotHasKey('blank', $usage);
    }
}
