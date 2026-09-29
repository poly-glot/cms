<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\PageRevision;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Override;

/**
 * @extends Table<array{}, PageRevision>
 */
final class PageRevisionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Pages');
        $this->belongsTo('Authors', [
            'className' => 'Users',
            'joinType' => 'INNER',
        ]);
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['page_id', 'version']), ['errorField' => 'version']);
        $rules->add($rules->existsIn(['page_id'], 'Pages'));
        $rules->add($rules->existsIn(['author_id'], 'Authors'));

        return $rules;
    }
}
