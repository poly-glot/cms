<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Tag;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Tag>
 */
final class TagsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('label', 'create')
            ->notEmptyString('label')
            ->maxLength('label', 80);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug', 'workspace_id']), ['errorField' => 'slug']);

        return $rules;
    }

    public function findOrCreateBySlug(string $labelOrSlug): Tag
    {
        return $this->findOrCreate(
            ['slug' => Tag::kebabCase($labelOrSlug)],
            fn (Tag $tag): Tag => $this->patchEntity($tag, ['label' => $labelOrSlug]),
            ['defaults' => false],
        );
    }
}
