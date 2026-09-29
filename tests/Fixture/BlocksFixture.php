<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class BlocksFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'block_type' => 'callout',
            'name' => 'Hours note',
            'data' => '{"variant":"info","body":"Open Mon–Fri."}',
            'author_id' => 1,
            'created' => '2026-01-05 10:00:00',
            'modified' => '2026-01-05 10:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'block_type' => 'quote',
            'name' => 'Founder quote',
            'data' => '{"text":"Made by hand.","attribution":"A. Cabinet"}',
            'author_id' => 1,
            'created' => '2026-01-05 10:00:00',
            'modified' => '2026-01-05 10:00:00',
        ],
        [
            'id' => 3,
            'workspace_id' => 1,
            'block_type' => 'image',
            'name' => 'Hero image',
            'data' => '{"media_id":1,"caption":"Workbench"}',
            'author_id' => 1,
            'created' => '2026-01-05 10:00:00',
            'modified' => '2026-01-05 10:00:00',
        ],
    ];
}
