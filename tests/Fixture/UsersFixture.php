<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class UsersFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'email' => 'admin@cabinet.local',
            'password' => '$2y$10$abcdefghijklmnopqrstuvCQiSkdMK4qVKXLP3.gQ.BCKZH7tFqHO',
            'name' => 'Admin',
            'created' => '2026-01-01 10:00:00',
            'modified' => '2026-01-01 10:00:00',
        ],
        [
            'id' => 2,
            'email' => 'eleanor@cabinet.local',
            'password' => '$2y$10$abcdefghijklmnopqrstuvCQiSkdMK4qVKXLP3.gQ.BCKZH7tFqHO',
            'name' => 'Eleanor Voss',
            'created' => '2026-01-02 10:00:00',
            'modified' => '2026-01-02 10:00:00',
        ],
        [
            'id' => 3,
            'email' => 'jun@cabinet.local',
            'password' => '$2y$10$abcdefghijklmnopqrstuvCQiSkdMK4qVKXLP3.gQ.BCKZH7tFqHO',
            'name' => 'Jun Park',
            'created' => '2026-01-03 10:00:00',
            'modified' => '2026-01-03 10:00:00',
        ],
    ];
}
