<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

final class TaggablesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');

        $this->belongsTo('Tags');
    }
}
