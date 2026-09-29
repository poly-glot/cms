<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class MenusFixture extends TestFixture
{
    public string $table = 'menus';

    /** @var array<int, array<string, mixed>> */
    public array $records = [
        ['id' => 1, 'workspace_id' => 1, 'name' => 'Main Menu', 'slug' => 'main', 'created' => '2026-01-04 10:00:00', 'modified' => '2026-01-04 10:00:00'],
        ['id' => 2, 'workspace_id' => 1, 'name' => 'Footer', 'slug' => 'footer', 'created' => '2026-01-04 10:00:00', 'modified' => '2026-01-04 10:00:00'],
    ];
}
