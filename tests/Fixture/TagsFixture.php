<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class TagsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        ['id' => 1, 'workspace_id' => 1, 'slug' => 'heritage', 'label' => 'heritage', 'created' => '2026-01-04 10:00:00'],
        ['id' => 2, 'workspace_id' => 1, 'slug' => 'company', 'label' => 'company', 'created' => '2026-01-04 10:00:00'],
    ];
}
