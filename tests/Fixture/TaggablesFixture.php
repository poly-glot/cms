<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

final class TaggablesFixture extends TestFixture
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [
        ['workspace_id' => 1, 'taggable_type' => 'Pages', 'taggable_id' => 1, 'tag_id' => 1],
        ['workspace_id' => 1, 'taggable_type' => 'Pages', 'taggable_id' => 1, 'tag_id' => 2],
    ];
}
