<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Menu;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Menu>
 */
final class MenusTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->hasMany('MenuItems', [
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->maxLength('name', 120);

        $validator
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->maxLength('slug', 120)
            ->regex('slug', '/^[a-z0-9\-]+$/', 'Use lowercase letters, numbers and hyphens only.');

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug', 'workspace_id']), ['errorField' => 'slug']);

        return $rules;
    }
}
