<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class MediaRenditionsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'media_id' => 1,
            'name' => 'large',
            'filename' => 'workbench-large.jpg',
            'width' => 1200,
            'height' => 800,
            'size' => 180000,
            'created' => '2026-01-04 10:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'media_id' => 1,
            'name' => 'thumb',
            'filename' => 'workbench-thumb.jpg',
            'width' => 240,
            'height' => 240,
            'size' => 9000,
            'created' => '2026-01-04 10:00:00',
        ],
    ];
}
