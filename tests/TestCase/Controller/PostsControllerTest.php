<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class PostsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /** @var array<int, string> */
    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Posts', 'app.Media', 'app.Blocks'];

    public function testBlogIndexListsLivePostsOnly(): void
    {
        $this->get('/cabinet/blog');

        $this->assertResponseOk();
        $this->assertResponseContains('Hello from the workshop');
        $this->assertResponseNotContains('Draft thoughts');
    }

    public function testLivePostRenders(): void
    {
        $this->get('/cabinet/blog/hello');

        $this->assertResponseOk();
        $this->assertResponseContains('Hello from the workshop');
    }

    public function testDraftPostReturns404(): void
    {
        $this->get('/cabinet/blog/draft-thoughts');

        $this->assertResponseCode(404);
    }

    public function testUnknownSlugReturns404(): void
    {
        $this->get('/cabinet/blog/no-such-post');

        $this->assertResponseCode(404);
    }

    public function testBlogPaginatesAtTenPerPage(): void
    {
        $this->seedLivePosts(15);

        $this->get('/cabinet/blog');
        $this->assertResponseOk();
        $this->assertResponseContains('post-pagination');
        $this->assertResponseNotContains('Hello from the workshop');

        $this->get('/cabinet/blog?page=2');
        $this->assertResponseOk();
        $this->assertResponseContains('Hello from the workshop');
    }

    private function seedLivePosts(int $count): void
    {
        $posts = $this->fetchTable('Posts');
        for ($i = 1; $i <= $count; ++$i) {
            $posts->saveOrFail($posts->newEntity([
                'title' => 'Bulk live ' . $i,
                'slug' => 'bulk-live-' . $i,
                'status' => 'live',
                'author_id' => 1,
                'published_at' => new DateTime('-' . $i . ' minutes'),
            ]));
        }
    }
}
