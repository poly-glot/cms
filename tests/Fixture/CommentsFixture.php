<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class CommentsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'commentable_type' => 'Pages',
            'commentable_id' => 1,
            'parent_id' => null,
            'author_name' => 'Marcus Hale',
            'author_email' => 'marcus@example.com',
            'body' => 'Loved the photo of the workbench. Any chance of shipping to the UK?',
            'status' => 'pending',
            'is_read' => false,
            'created' => '2026-05-30 09:00:00',
            'modified' => '2026-05-30 09:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'commentable_type' => 'Pages',
            'commentable_id' => 1,
            'parent_id' => null,
            'author_name' => 'Saoirse Lin',
            'author_email' => null,
            'body' => 'Is the lifetime repair promise transferable if the piece is sold on?',
            'status' => 'pending',
            'is_read' => false,
            'created' => '2026-05-30 07:00:00',
            'modified' => '2026-05-30 07:00:00',
        ],
        [
            'id' => 3,
            'workspace_id' => 1,
            'commentable_type' => 'Pages',
            'commentable_id' => 2,
            'parent_id' => null,
            'author_name' => 'Jun Park',
            'author_email' => 'jun@example.com',
            'body' => 'Mine arrived this morning. Beautifully packed.',
            'status' => 'approved',
            'is_read' => true,
            'created' => '2026-05-29 12:00:00',
            'modified' => '2026-05-29 12:00:00',
        ],
        [
            'id' => 4,
            'workspace_id' => 1,
            'commentable_type' => 'Pages',
            'commentable_id' => 2,
            'parent_id' => 3,
            'author_name' => 'Moderator',
            'author_email' => null,
            'body' => 'Thank you, Jun — glad it arrived safely!',
            'status' => 'approved',
            'is_read' => true,
            'created' => '2026-05-29 13:00:00',
            'modified' => '2026-05-29 13:00:00',
        ],
    ];
}
