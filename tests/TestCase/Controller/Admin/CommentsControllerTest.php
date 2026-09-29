<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class CommentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Pages', 'app.Posts', 'app.Comments'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexListsTopLevelComments(): void
    {
        $this->get('/cabinet/admin/comments');

        $this->assertResponseOk();
        $this->assertResponseContains('Marcus Hale');
        $this->assertResponseContains('Jun Park');
        $this->assertResponseContains('on &ldquo;About Us&rdquo;');
        $this->assertResponseContains('on &ldquo;Draft Page&rdquo;');
        // The reply (id 4) is nested, not listed as its own moderation row.
        $this->assertResponseContains('Thank you, Jun');
    }

    public function testIndexShowsThePostTitleForAPostComment(): void
    {
        $comments = $this->fetchTable('Comments');
        $comments->saveOrFail($comments->newEntity([
            'commentable_type' => 'Posts',
            'commentable_id' => 1,
            'author_name' => 'Post Reader',
            'body' => 'On a post.',
        ]));

        $this->get('/cabinet/admin/comments');

        $this->assertResponseOk();
        $this->assertResponseContains('on &ldquo;Hello from the workshop&rdquo;');
    }

    public function testApproveSetsApprovedAndRead(): void
    {
        $this->post('/cabinet/admin/comments/approve/1');

        $this->assertRedirect('/cabinet/admin/comments');
        $comment = $this->fetchTable('Comments')->get(1);
        $this->assertSame('approved', $comment->status);
        $this->assertTrue($comment->is_read);
    }

    public function testSpamSetsSpam(): void
    {
        $this->post('/cabinet/admin/comments/spam/1');

        $this->assertRedirect('/cabinet/admin/comments');
        $this->assertSame('spam', $this->fetchTable('Comments')->get(1)->status);
    }

    public function testDeleteRemovesComment(): void
    {
        $this->post('/cabinet/admin/comments/delete/1');

        $this->assertRedirect('/cabinet/admin/comments');
        $this->assertFalse($this->fetchTable('Comments')->exists(['id' => 1]));
    }

    public function testReplyCreatesApprovedChild(): void
    {
        $this->post('/cabinet/admin/comments/reply/1', ['body' => 'Thanks for the kind words!']);

        $this->assertRedirect('/cabinet/admin/comments');
        $comments = $this->fetchTable('Comments');
        $reply = $comments->find()->where(['parent_id' => 1])->firstOrFail();
        $this->assertSame('approved', $reply->status);
        $this->assertSame('Pages', $reply->commentable_type);
        $this->assertSame(1, $reply->commentable_id);
        $this->assertSame('Admin', $reply->author_name);
    }

    public function testApproveSelectedApprovesOnlyPending(): void
    {
        $this->post('/cabinet/admin/comments/approve-selected', ['ids' => [1, 2]]);

        $this->assertRedirect('/cabinet/admin/comments');
        $comments = $this->fetchTable('Comments');
        $this->assertSame('approved', $comments->get(1)->status);
        $this->assertSame('approved', $comments->get(2)->status);
    }

    public function testMarkAllReadClearsUnread(): void
    {
        $this->post('/cabinet/admin/comments/mark-all-read');

        $this->assertRedirect('/cabinet/admin/comments');
        $unread = $this->fetchTable('Comments')->find()->where(['is_read' => false])->count();
        $this->assertSame(0, $unread);
    }

    public function testReplyApprovesAStillPendingParent(): void
    {
        $this->post('/cabinet/admin/comments/reply/1', ['body' => 'Thanks for writing in!']);

        $this->assertRedirect('/cabinet/admin/comments');
        $this->assertSame('approved', $this->fetchTable('Comments')->get(1)->status);
    }

    public function testReplyRejectsEmptyBody(): void
    {
        $comments = $this->fetchTable('Comments');
        $before = $comments->find()->where(['parent_id' => 1])->count();

        $this->post('/cabinet/admin/comments/reply/1', ['body' => '   ']);

        $this->assertRedirect('/cabinet/admin/comments');
        $this->assertSame($before, $comments->find()->where(['parent_id' => 1])->count());
    }

    public function testApproveSelectedSkipsNonPendingComments(): void
    {
        $comments = $this->fetchTable('Comments');
        $comments->updateAll(['status' => 'spam'], ['id' => 1]);

        $this->post('/cabinet/admin/comments/approve-selected', ['ids' => [1]]);

        $this->assertSame('spam', $comments->get(1)->status);
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/comments');

        $this->assertRedirectContains('/login');
    }
}
