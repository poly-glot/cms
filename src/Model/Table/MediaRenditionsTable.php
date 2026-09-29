<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\MediaRendition;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, MediaRendition>
 */
final class MediaRenditionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Media', ['joinType' => 'INNER']);
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('name', 'create')
            ->inList('name', ['large', 'medium', 'small', 'thumb']);

        $validator
            ->requirePresence('filename', 'create')
            ->notEmptyString('filename')
            ->maxLength('filename', 255);

        $validator
            ->requirePresence('size', 'create')
            ->integer('size');

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['media_id', 'name']), ['errorField' => 'name']);
        $rules->add($rules->existsIn(['media_id'], 'Media'), ['errorField' => 'media_id']);

        return $rules;
    }
}
