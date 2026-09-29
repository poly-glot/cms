<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class PostsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'title' => 'Hello from the workshop',
            'slug' => 'hello',
            'excerpt' => 'A first journal entry.',
            'body' => '<p>A first journal entry from the workshop.</p>',
            'status' => 'live',
            'published_at' => '2026-01-10 09:00:00',
            'comments_enabled' => false,
            'author_id' => 1,
            'created' => '2026-01-10 09:00:00',
            'modified' => '2026-01-10 09:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'title' => 'Draft thoughts',
            'slug' => 'draft-thoughts',
            'excerpt' => null,
            'body' => '<p>Not yet ready.</p>',
            'status' => 'draft',
            'published_at' => null,
            'comments_enabled' => false,
            'author_id' => 3,
            'created' => '2026-01-12 09:00:00',
            'modified' => '2026-01-12 09:00:00',
        ],
    ];
}
