<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class PostsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Posts', 'app.Tags', 'app.Taggables', 'app.ContentTypeFieldSchemas'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexListsPosts(): void
    {
        $this->get('/cabinet/admin/posts');

        $this->assertResponseOk();
        $this->assertResponseContains('Hello from the workshop');
        $this->assertResponseContains('Draft thoughts');
        $this->assertResponseContains('/cabinet/admin/posts/add');
    }

    public function testIndexFiltersByStatus(): void
    {
        $this->get('/cabinet/admin/posts?status=draft');

        $this->assertResponseOk();
        $this->assertResponseContains('Draft thoughts');
        $this->assertResponseNotContains('Hello from the workshop');
    }

    public function testIndexKeywordSearchNarrowsResults(): void
    {
        $this->get('/cabinet/admin/posts?q=workshop');

        $this->assertResponseOk();
        $this->assertResponseContains('Hello from the workshop');
        $this->assertResponseNotContains('Draft thoughts');
    }

    public function testIndexPaginatesAtTwentyPerPage(): void
    {
        $this->seedLivePosts(25);

        $this->get('/cabinet/admin/posts');
        $this->assertResponseOk();
        $this->assertResponseContains('cms-pagination');
        $this->assertResponseNotContains('Hello from the workshop');

        $this->get('/cabinet/admin/posts?page=2');
        $this->assertResponseOk();
        $this->assertResponseContains('Hello from the workshop');
    }

    private function seedLivePosts(int $count): void
    {
        $posts = $this->fetchTable('Posts');
        for ($i = 1; $i <= $count; ++$i) {
            $posts->saveOrFail($posts->newEntity([
                'title' => 'Bulk post ' . $i,
                'slug' => 'bulk-post-' . $i,
                'status' => 'live',
                'author_id' => 1,
                'published_at' => new DateTime('-' . $i . ' minutes'),
            ]));
        }
    }

    public function testAddCreatesPostAndRedirectsToEdit(): void
    {
        $this->post('/cabinet/admin/posts/add', [
            'title' => 'New Post',
            'slug' => 'new-post',
            'body' => '<p>Brand new.</p>',
            'status' => 'draft',
        ]);

        $created = $this->fetchTable('Posts')
            ->find()->where(['slug' => 'new-post'])->firstOrFail();
        $this->assertRedirect('/cabinet/admin/posts/edit/' . (int) $created->id);
        $this->assertSame(1, $created->author_id);
    }

    public function testEditPersistsChanges(): void
    {
        $this->post('/cabinet/admin/posts/edit/1', [
            'title' => 'Hello, edited',
            'slug' => 'hello',
            'body' => '<p>Edited.</p>',
            'status' => 'live',
        ]);

        $this->assertRedirect('/cabinet/admin/posts/edit/1');
        $this->assertSame('Hello, edited', $this->fetchTable('Posts')->get(1)->title);
    }

    public function testDeleteRemovesPost(): void
    {
        $this->post('/cabinet/admin/posts/delete/1');

        $this->assertRedirect('/cabinet/admin/posts');
        $this->assertFalse($this->fetchTable('Posts')->exists(['id' => 1]));
    }

    public function testPublishMovesToLiveWithPublishedAt(): void
    {
        $this->post('/cabinet/admin/posts/publish/2');

        $this->assertRedirect('/cabinet/admin/posts/edit/2');
        $post = $this->fetchTable('Posts')->get(2);
        $this->assertSame('live', $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function testScheduleRequiresFutureDate(): void
    {
        $future = new DateTime('+7 days')->format('Y-m-d H:i:s');
        $this->post('/cabinet/admin/posts/schedule/2', ['published_at' => $future]);

        $this->assertRedirect('/cabinet/admin/posts/edit/2');
        $post = $this->fetchTable('Posts')->get(2);
        $this->assertSame('scheduled', $post->status);
    }

    public function testScheduleRejectsPastDate(): void
    {
        $this->post('/cabinet/admin/posts/schedule/2', ['published_at' => '2020-01-01 09:00:00']);

        $this->assertRedirect('/cabinet/admin/posts/edit/2');
        $post = $this->fetchTable('Posts')->get(2);
        $this->assertSame('draft', $post->status);
    }

    public function testAuthorCannotEditAnotherAuthorsPost(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'author']]);
        $this->post('/cabinet/admin/posts/edit/1', [
            'title' => 'Hijacked', 'slug' => 'hello', 'status' => 'live',
        ]);

        $this->assertResponseCode(403);
    }

    public function testAuthorCannotPublishOwnPost(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'author']]);
        $this->post('/cabinet/admin/posts/publish/2');

        $this->assertResponseCode(403);
    }

    public function testAttachTagAddsJoin(): void
    {
        $this->post('/cabinet/admin/posts/1/tags/brand-new');

        $this->assertResponseOk();
        $joins = $this->fetchTable('Taggables');
        $tag = $this->fetchTable('Tags')
            ->find()->where(['slug' => 'brand-new'])->firstOrFail();
        $this->assertTrue($joins->exists(['taggable_type' => 'Posts', 'taggable_id' => 1, 'tag_id' => $tag->id]));
    }

    public function testDetachTagRemovesJoinKeepsTag(): void
    {
        $joins = $this->fetchTable('Taggables');
        $joins->save($joins->newEntity(['taggable_type' => 'Posts', 'taggable_id' => 1, 'tag_id' => 1]));

        $this->post('/cabinet/admin/posts/1/tags/heritage/detach');

        $this->assertFalse($joins->exists(['taggable_type' => 'Posts', 'taggable_id' => 1, 'tag_id' => 1]));
        $this->assertTrue($this->fetchTable('Tags')->exists(['slug' => 'heritage']));
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/posts');

        $this->assertRedirectContains('/login');
    }
}
