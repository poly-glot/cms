<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class MembershipsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        ['id' => 1, 'workspace_id' => 1, 'user_id' => 1, 'role' => 'admin', 'created' => '2026-01-01 10:00:00', 'modified' => '2026-01-01 10:00:00'],
        ['id' => 2, 'workspace_id' => 1, 'user_id' => 2, 'role' => 'editor', 'created' => '2026-01-01 10:00:00', 'modified' => '2026-01-01 10:00:00'],
        ['id' => 3, 'workspace_id' => 1, 'user_id' => 3, 'role' => 'contributor', 'created' => '2026-01-01 10:00:00', 'modified' => '2026-01-01 10:00:00'],
        ['id' => 4, 'workspace_id' => 2, 'user_id' => 1, 'role' => 'admin', 'created' => '2026-01-01 10:00:00', 'modified' => '2026-01-01 10:00:00'],
    ];
}
