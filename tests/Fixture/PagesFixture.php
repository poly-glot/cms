<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class PagesFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'title' => 'About Us',
            'slug' => 'about',
            'body' => '<p>Made by hand, since 1992.</p>',
            'status' => 'live',
            'published_at' => null,
            'template' => 'default',
            'visibility' => 'public',
            'parent_id' => null,
            'position' => 0,
            'author_id' => 1,
            'created' => '2026-01-02 10:00:00',
            'modified' => '2026-01-02 10:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'body' => '<p>Not yet published.</p>',
            'status' => 'draft',
            'published_at' => null,
            'template' => 'default',
            'visibility' => 'public',
            'parent_id' => null,
            'position' => 1,
            'author_id' => 1,
            'created' => '2026-01-03 10:00:00',
            'modified' => '2026-01-03 10:00:00',
        ],
        [
            'id' => 3,
            'workspace_id' => 1,
            'title' => 'Press',
            'slug' => 'press',
            'body' => '<p>Press materials.</p>',
            'status' => 'live',
            'published_at' => null,
            'template' => 'default',
            'visibility' => 'public',
            'parent_id' => 1,
            'position' => 0,
            'author_id' => 1,
            'created' => '2026-01-04 10:00:00',
            'modified' => '2026-01-04 10:00:00',
        ],
    ];
}
