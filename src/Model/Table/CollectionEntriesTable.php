<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Behavior\EditorialContentBehavior;
use App\Model\Entity\Collection;
use App\Model\Entity\CollectionEntry;
use App\Model\Enum\PostStatus;
use App\Service\Collection\ReferenceReconciler;
use App\Service\Collection\TagSyncer;
use App\Service\FieldSchema\FieldDataSanitizer;
use ArrayObject;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{EditorialContent: EditorialContentBehavior}, CollectionEntry>
 */
final class CollectionEntriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');
        $this->addBehavior('EditorialContent', ['slugFromTitle' => true]);

        $this->belongsTo('Collections', ['joinType' => 'INNER']);
        $this->belongsTo('Authors', [
            'className' => 'Users',
            'joinType' => 'INNER',
        ]);
        $this->belongsToMany('Tags', [
            'through' => 'Taggables',
            'foreignKey' => 'taggable_id',
            'conditions' => ['Taggables.taggable_type' => 'CollectionEntries'],
        ]);
        $this->hasMany('IncomingReferences', [
            'className' => 'CollectionEntryReferences',
            'foreignKey' => 'target_entry_id',
        ]);
    }

    /**
     * @param SelectQuery<CollectionEntry> $query
     * @return SelectQuery<CollectionEntry>
     */
    public function findTitleSearch(SelectQuery $query, int $collectionId, string $term): SelectQuery
    {
        $query = $query->where(['CollectionEntries.collection_id' => $collectionId]);

        if ($term !== '') {
            $query = $query->where(static fn (QueryExpression $exp): QueryExpression => $exp->like('CollectionEntries.title', '%' . $term . '%'));
        }

        return $query->orderByDesc('CollectionEntries.modified')->limit(20);
    }

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity instanceof CollectionEntry) {
            new ReferenceReconciler()->reconcile($entity);
            new TagSyncer()->sync($entity);
        }
    }

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity instanceof CollectionEntry) {
            new TagSyncer()->clear($entity);
        }
    }

    /**
     * @param EventInterface<Table> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$entity instanceof CollectionEntry || !$entity->isDirty('data')) {
            return;
        }

        $collection = $this->getAssociation('Collections')->find()
            ->where(['Collections.id' => $entity->collection_id])
            ->first();

        if ($collection instanceof Collection) {
            $entity->data = new FieldDataSanitizer()->clean($collection->fields, $entity->data);
        }
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('title', 'create')
            ->notEmptyString('title')
            ->maxLength('title', 200);

        $validator
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->maxLength('slug', 160)
            ->regex('slug', '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', 'Slug must be lowercase letters, digits, or hyphens.');

        $validator
            ->requirePresence('collection_id', 'create')
            ->integer('collection_id');

        $validator
            ->requirePresence('author_id', 'create')
            ->integer('author_id');

        $validator
            ->requirePresence('status', 'create')
            ->enum('status', PostStatus::class, 'Status must be draft, live, or scheduled.');

        $validator->allowEmptyDateTime('published_at');
        $validator->boolean('comments_enabled');
        $validator->add('data', 'shape', [
            'rule' => is_array(...),
            'message' => 'Entry data must be a key/value map.',
        ]);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(
            $rules->isUnique(['collection_id', 'slug'], 'Slug must be unique within this collection.'),
            ['errorField' => 'slug'],
        );
        $rules->add($rules->existsIn(['collection_id'], 'Collections'), ['errorField' => 'collection_id']);
        $rules->add($rules->existsIn(['author_id'], 'Authors'), ['errorField' => 'author_id']);

        $rules->addDelete($rules->isNotLinkedTo('IncomingReferences'), 'notReferenced', [
            'errorField' => 'id',
            'message' => 'This entry is linked from other entries — remove those links first.',
        ]);

        return $rules;
    }

    /**
     * @param SelectQuery<CollectionEntry> $query
     * @return SelectQuery<CollectionEntry>
     */
    public function findForCollection(SelectQuery $query, int $collectionId): SelectQuery
    {
        return $query->where(['CollectionEntries.collection_id' => $collectionId]);
    }

    /**
     * @param SelectQuery<CollectionEntry> $query
     * @return SelectQuery<CollectionEntry>
     */
    public function findLive(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('status') => PostStatus::Live->value]);
    }

    /**
     * @param SelectQuery<CollectionEntry> $query
     * @return SelectQuery<CollectionEntry>
     */
    public function findByCollectionSlug(SelectQuery $query, string $collectionSlug): SelectQuery
    {
        return $query->innerJoinWith(
            'Collections',
            static fn (SelectQuery $collections): SelectQuery => $collections->where(['Collections.slug' => $collectionSlug]),
        );
    }
}
