<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class PageRevisionsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'page_id' => 1,
            'version' => 1,
            'author_id' => 1,
            'title' => 'About Us',
            'slug' => 'about',
            'body' => '<p>Initial version.</p>',
            'status' => 'draft',
            'template' => 'default',
            'published_at' => null,
            'parent_id' => null,
            'visibility' => 'public',
            'note' => null,
            'created' => '2026-01-02 10:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'page_id' => 1,
            'version' => 2,
            'author_id' => 1,
            'title' => 'About Us',
            'slug' => 'about',
            'body' => '<p>Made by hand, since 1992.</p>',
            'status' => 'live',
            'template' => 'default',
            'published_at' => null,
            'parent_id' => null,
            'visibility' => 'public',
            'note' => null,
            'created' => '2026-01-02 11:00:00',
        ],
    ];
}
