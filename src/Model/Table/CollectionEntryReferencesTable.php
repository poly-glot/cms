<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\CollectionEntryReference;
use Cake\ORM\Table;

/**
 * @extends Table<array{}, CollectionEntryReference>
 */
final class CollectionEntryReferencesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->belongsTo('TargetEntries', [
            'className' => 'CollectionEntries',
            'joinType' => 'INNER',
        ]);
    }
}
