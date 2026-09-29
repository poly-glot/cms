<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class PageBlocksFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        ['workspace_id' => 1, 'page_id' => 1, 'block_id' => 1],
    ];
}
