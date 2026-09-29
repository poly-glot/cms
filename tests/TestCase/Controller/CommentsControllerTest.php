<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class CommentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Pages', 'app.Posts', 'app.Comments', 'app.Settings'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    public function testAddCreatesPendingComment(): void
    {
        $this->post('/cabinet/comments', [
            'page_id' => 1,
            'author_name' => 'New Visitor',
            'author_email' => 'visitor@example.com',
            'body' => 'Beautiful work, thank you.',
        ]);

        $this->assertResponseCode(302);
        $comment = $this->fetchTable('Comments')
            ->find()->where(['author_name' => 'New Visitor'])->firstOrFail();
        $this->assertSame('pending', $comment->status);
        $this->assertSame('Pages', $comment->commentable_type);
        $this->assertSame(1, $comment->commentable_id);
        $this->assertFalse($comment->is_read);
    }

    public function testAddCannotSelfApproveViaMassAssignment(): void
    {
        $this->post('/cabinet/comments', [
            'page_id' => 1,
            'author_name' => 'Sneaky',
            'body' => 'Trying to self-approve.',
            'status' => 'approved',
        ]);

        $comment = $this->fetchTable('Comments')
            ->find()->where(['author_name' => 'Sneaky'])->firstOrFail();
        $this->assertSame('pending', $comment->status);
    }

    public function testAddRejectsEmptyBody(): void
    {
        $this->post('/cabinet/comments', [
            'page_id' => 1,
            'author_name' => 'No Body',
            'body' => '',
        ]);

        $this->assertFalse(
            $this->fetchTable('Comments')->exists(['author_name' => 'No Body']),
        );
    }

    public function testAddRejectsCommentForUnknownPage(): void
    {
        $this->post('/cabinet/comments', [
            'page_id' => 99999,
            'author_name' => 'Orphan',
            'body' => 'No such page.',
        ]);

        $this->assertFalse(
            $this->fetchTable('Comments')->exists(['author_name' => 'Orphan']),
        );
    }

    public function testApprovedCommentRendersEscapedOnPublicPage(): void
    {
        // A stored payload must never reach the page as live markup.
        $comments = $this->fetchTable('Comments');
        $comment = $comments->newEntity([
            'author_name' => 'XSS <b>Tester</b>',
            'body' => '<script>alert(1)</script>',
        ]);
        $comment->commentable_type = 'Pages';
        $comment->commentable_id = 1;
        $comment->status = 'approved';
        $comments->saveOrFail($comment);

        $this->get('/cabinet/about');

        $this->assertResponseOk();
        $this->assertResponseNotContains('<script>alert(1)</script>');
        $this->assertResponseContains('&lt;script&gt;alert(1)&lt;/script&gt;');
    }

    public function testAddRejectedWhenCommentsDisabled(): void
    {
        $this->fetchTable('Settings')->writeMany(['allow_comments' => '0']);

        $this->post('/cabinet/comments', [
            'page_id' => 1,
            'author_name' => 'Too Late',
            'body' => 'Comments are off.',
        ]);

        $this->assertFalse(
            $this->fetchTable('Comments')->exists(['author_name' => 'Too Late']),
        );
    }

    public function testAddRejectedWhenPageHasCommentsDisabled(): void
    {
        $this->fetchTable('Pages')->updateAll(['comments_enabled' => false], ['id' => 1]);

        $this->post('/cabinet/comments', [
            'page_id' => 1,
            'author_name' => 'Page Closed',
            'body' => 'This page has comments off.',
        ]);

        $this->assertFalse(
            $this->fetchTable('Comments')->exists(['author_name' => 'Page Closed']),
        );
    }

    public function testAddAutoApprovesWhenModerationOff(): void
    {
        $this->fetchTable('Settings')->writeMany(['moderation_queue' => '0']);

        $this->post('/cabinet/comments', [
            'page_id' => 1,
            'author_name' => 'Trusted',
            'body' => 'Should appear immediately.',
        ]);

        $comment = $this->fetchTable('Comments')
            ->find()->where(['author_name' => 'Trusted'])->firstOrFail();
        $this->assertSame('approved', $comment->status);
    }

    public function testAddCreatesCommentOnPostWhenEnabled(): void
    {
        $this->fetchTable('Posts')->updateAll(['comments_enabled' => true], ['id' => 1]);

        $this->post('/cabinet/comments', [
            'commentable_type' => 'Posts',
            'commentable_id' => 1,
            'author_name' => 'Post Fan',
            'body' => 'Loved the journal entry.',
        ]);

        $comment = $this->fetchTable('Comments')
            ->find()->where(['author_name' => 'Post Fan'])->firstOrFail();
        $this->assertSame('Posts', $comment->commentable_type);
        $this->assertSame(1, $comment->commentable_id);
    }

    public function testAddRejectsCommentOnPostWhenDisabled(): void
    {
        $this->post('/cabinet/comments', [
            'commentable_type' => 'Posts',
            'commentable_id' => 1,
            'author_name' => 'Locked Out',
            'body' => 'Should be rejected — comments disabled by default.',
        ]);

        $this->assertFalse(
            $this->fetchTable('Comments')->exists(['author_name' => 'Locked Out']),
        );
    }

    public function testAddRejectsUnknownCommentableType(): void
    {
        $this->post('/cabinet/comments', [
            'commentable_type' => 'Users',
            'commentable_id' => 1,
            'author_name' => 'Sneaky',
            'body' => 'Trying to comment on a user row.',
        ]);

        $this->assertFalse(
            $this->fetchTable('Comments')->exists(['author_name' => 'Sneaky']),
        );
    }
}
