<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

final class CommentsTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Pages', 'app.Comments'];

    public function testFindApprovedForPageReturnsOnlyApprovedTopLevel(): void
    {
        // Fixture: page 1 has two pending top-level comments (ids 1, 2).
        $approved = $this->fetchTable('Comments')->find('approvedFor', commentableType: 'Pages', commentableId: 1)->all()->toList();

        $this->assertSame([], $approved);
    }

    public function testFindApprovedForPageIncludesApprovedThreadWithApprovedReplies(): void
    {
        // Fixture: page 2 has approved comment 3 with approved reply 4.
        $threads = $this->fetchTable('Comments')->find('approvedFor', commentableType: 'Pages', commentableId: 2)->all()->toList();

        $this->assertCount(1, $threads);
        $this->assertSame('Jun Park', $threads[0]->author_name);
        $this->assertCount(1, $threads[0]->children);
        $this->assertSame('Moderator', $threads[0]->children[0]->author_name);
    }

    public function testFindApprovedForPageExcludesPendingReplies(): void
    {
        $comments = $this->fetchTable('Comments');
        $pendingReply = $comments->newEntity([
            'commentable_type' => 'Pages',
            'commentable_id' => 2,
            'parent_id' => 3,
            'author_name' => 'Spammer',
            'body' => 'Hidden pending reply',
        ]);
        $comments->saveOrFail($pendingReply);

        $threads = $comments->find('approvedFor', commentableType: 'Pages', commentableId: 2)->all()->toList();

        $this->assertCount(1, $threads[0]->children, 'A pending reply must not surface publicly.');
        $this->assertSame('Moderator', $threads[0]->children[0]->author_name);
    }

    public function testUnreadCountCountsOnlyUnreadRows(): void
    {
        // Fixture: comments 1 and 2 are unread; 3 and 4 are read.
        $this->assertSame(2, $this->fetchTable('Comments')->unreadCount());
    }
}
