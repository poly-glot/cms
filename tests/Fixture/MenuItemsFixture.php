<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class MenuItemsFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        ['id' => 1, 'workspace_id' => 1, 'menu_id' => 1, 'parent_id' => null, 'position' => 0, 'title' => 'Home', 'type' => 'url', 'page_id' => null, 'url' => '/', 'target' => '_self', 'created' => '2026-01-04 10:00:00', 'modified' => '2026-01-04 10:00:00'],
        ['id' => 2, 'workspace_id' => 1, 'menu_id' => 1, 'parent_id' => null, 'position' => 1, 'title' => 'About', 'type' => 'page', 'page_id' => 1, 'url' => null, 'target' => '_self', 'created' => '2026-01-04 10:00:00', 'modified' => '2026-01-04 10:00:00'],
        ['id' => 3, 'workspace_id' => 1, 'menu_id' => 1, 'parent_id' => 2, 'position' => 0, 'title' => 'Press', 'type' => 'page', 'page_id' => 2, 'url' => null, 'target' => '_self', 'created' => '2026-01-04 10:00:00', 'modified' => '2026-01-04 10:00:00'],
        ['id' => 4, 'workspace_id' => 1, 'menu_id' => 1, 'parent_id' => null, 'position' => 2, 'title' => 'Shop', 'type' => 'url', 'page_id' => null, 'url' => 'https://shop.example.com', 'target' => '_blank', 'created' => '2026-01-04 10:00:00', 'modified' => '2026-01-04 10:00:00'],
    ];
}
