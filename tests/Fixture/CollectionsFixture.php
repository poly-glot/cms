<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class CollectionsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'name' => 'Products',
            'slug' => 'products',
            'description' => 'Sellable items.',
            'field_schema' => '[{"name":"price","label":"Price","type":"number","required":true},{"name":"description","label":"Description","type":"textarea","required":false}]',
            'created' => '2026-02-01 10:00:00',
            'modified' => '2026-02-01 10:00:00',
        ],
    ];
}
