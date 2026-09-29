<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Collection;
use App\Model\FieldSchema\FieldSchema;
use App\Service\FieldSchema\SchemaDefinitionValidator;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Collection>
 */
final class CollectionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->hasMany('CollectionEntries', ['dependent' => true]);
    }

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $data
     * @param ArrayObject<string, mixed> $options
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['field_schema'])) {
            $data['field_schema'] = FieldSchema::normalise($data['field_schema']);
        }
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
            ->maxLength('slug', 80)
            ->regex('slug', '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', 'Slug must be lowercase letters, digits, or hyphens.');

        $validator->allowEmptyString('description')->maxLength('description', 500);

        $validator->add('field_schema', 'shape', [
            'rule' => static fn (mixed $value): bool|string => new SchemaDefinitionValidator()->validate($value),
        ]);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug', 'workspace_id']), ['errorField' => 'slug']);

        return $rules;
    }

    /**
     * @param SelectQuery<Collection> $query
     * @return SelectQuery<Collection>
     */
    public function findForSync(SelectQuery $query, string $slug): SelectQuery
    {
        $query = $query->orderByAsc('Collections.slug');

        return $slug === '' ? $query : $query->where(['Collections.slug' => $slug]);
    }

    /**
     * @param SelectQuery<Collection> $query
     * @return SelectQuery<Collection>
     */
    public function findForReferencePicker(SelectQuery $query): SelectQuery
    {
        return $query
            ->select(['Collections.id', 'Collections.name', 'Collections.slug'])
            ->orderByAsc('Collections.name');
    }
}
