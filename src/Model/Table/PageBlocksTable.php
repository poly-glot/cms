<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\PageBlock;
use Cake\ORM\Table;

/**
 * @extends Table<array{}, PageBlock>
 */
final class PageBlocksTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');

        $this->belongsTo('Pages');
        $this->belongsTo('Blocks');
    }
}
