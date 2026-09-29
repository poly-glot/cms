<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class MediaFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'name' => 'workbench.jpg',
            'filename' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'mime' => 'image/jpeg',
            'size' => 256000,
            'width' => 1200,
            'height' => 800,
            'alt' => 'A walnut workbench',
            'uploaded_by' => 1,
            'created' => '2026-01-04 10:00:00',
            'modified' => '2026-01-04 10:00:00',
        ],
    ];
}
