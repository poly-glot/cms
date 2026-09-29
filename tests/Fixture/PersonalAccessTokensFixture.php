<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class PersonalAccessTokensFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        [
            'id' => 1,
            'workspace_id' => 1,
            'user_id' => 1,
            'name' => 'Preview build token',
            'token_hash' => 'd90454853ebe393841c180fce2a95d6eb7c12a3f15e4e34a9b04af292b58468c',
            'scopes' => ['read', 'preview'],
            'last_used_at' => null,
            'expires_at' => null,
            'created' => '2026-02-01 10:00:00',
            'modified' => '2026-02-01 10:00:00',
        ],
        [
            'id' => 2,
            'workspace_id' => 1,
            'user_id' => 1,
            'name' => 'Admin write token',
            'token_hash' => '11537feeedb3343a72bf1a7ce69c22ad6f6de64308feb8e86acf61e904d98179',
            'scopes' => ['read', 'write'],
            'last_used_at' => null,
            'expires_at' => null,
            'created' => '2026-02-01 10:00:00',
            'modified' => '2026-02-01 10:00:00',
        ],
        [
            'id' => 3,
            'workspace_id' => 1,
            'user_id' => 2,
            'name' => 'Editor write token',
            'token_hash' => '3aa677a218a7b1d94eaa96c8b1841db2fd9ec47920a1fdb775910fa66645390f',
            'scopes' => ['read', 'write'],
            'last_used_at' => null,
            'expires_at' => null,
            'created' => '2026-02-01 10:00:00',
            'modified' => '2026-02-01 10:00:00',
        ],
        [
            'id' => 4,
            'workspace_id' => 1,
            'user_id' => 3,
            'name' => 'Contributor write token',
            'token_hash' => '0671f66b6a7835b43cc15b4f8942ef7d087c7bee13c07ca0c6e968053ddb3086',
            'scopes' => ['read', 'write'],
            'last_used_at' => null,
            'expires_at' => null,
            'created' => '2026-02-01 10:00:00',
            'modified' => '2026-02-01 10:00:00',
        ],
        [
            'id' => 5,
            'workspace_id' => 2,
            'user_id' => 2,
            'name' => 'Unmembered write token',
            'token_hash' => '55a39d9ff8a67ec9b155f355ca29570573af9c9e1b57e051910f83a411909c2e',
            'scopes' => ['read', 'write'],
            'last_used_at' => null,
            'expires_at' => null,
            'created' => '2026-02-01 10:00:00',
            'modified' => '2026-02-01 10:00:00',
        ],
    ];
}
