<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class CollectionEntriesFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'collection_id' => 1,
            'title' => 'Maple Chair',
            'slug' => 'maple-chair',
            'data' => '{"price":420,"description":"Hand-finished maple."}',
            'status' => 'live',
            'published_at' => '2026-02-05 10:00:00',
            'author_id' => 1,
            'created' => '2026-02-05 10:00:00',
            'modified' => '2026-02-05 10:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'collection_id' => 1,
            'title' => 'Walnut Stool',
            'slug' => 'walnut-stool',
            'data' => '{"price":180,"description":"Compact walnut stool."}',
            'status' => 'draft',
            'published_at' => null,
            'author_id' => 3,
            'created' => '2026-02-06 10:00:00',
            'modified' => '2026-02-06 10:00:00',
        ],
    ];
}
