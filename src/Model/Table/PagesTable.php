<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Behavior\EditorialContentBehavior;
use App\Model\Entity\Page;
use App\Model\Enum\PostStatus;
use App\Service\Page\BlockUsageIndexer;
use ArrayObject;
use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{EditorialContent: EditorialContentBehavior}, Page>
 */
final class PagesTable extends Table
{
    private const array SLUG_BLOCKLIST = ['admin', 'login', 'logout', '_assets', 'webroot'];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');
        $this->addBehavior('EditorialContent', ['noun' => 'pages']);

        $this->belongsTo('Authors', [
            'className' => 'Users',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('ParentPages', [
            'className' => 'Pages',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('PageRevisions');
        $this->belongsToMany('Tags', [
            'through' => 'Taggables',
            'foreignKey' => 'taggable_id',
            'conditions' => ['Taggables.taggable_type' => 'Pages'],
        ]);
    }

    /**
     * Keep the block usage index in sync on every page persist (incl. autosave).
     *
     * @param EventInterface<PagesTable> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity instanceof Page) {
            new BlockUsageIndexer()->reindex($entity);
            // A page's slug/status/parent feed public menu links; drop the
            // resolved-menu cache so navigation reflects the change.
            Cache::clear('menus');
        }
    }

    /**
     * @param EventInterface<PagesTable> $event
     * @param ArrayObject<string, mixed> $options
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        Cache::clear('menus');
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
            ->regex('slug', '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', 'Slug must be lowercase letters, digits, or hyphens.')
            ->add('slug', 'notReserved', [
                'rule' => static function (string $value, array $context): bool {
                    /** @var array{data?: array<string, mixed>} $context */
                    $parentId = $context['data']['parent_id'] ?? null;
                    if ($parentId !== null) {
                        return true;
                    }

                    return !in_array($value, self::SLUG_BLOCKLIST, true);
                },
                'message' => 'This slug is reserved and cannot be used.',
            ]);

        $validator
            ->requirePresence('status', 'create')
            ->enum('status', PostStatus::class, 'Status must be draft, live, or scheduled.');

        $validator->add('template', 'inEnum', [
            'rule' => ['inList', ['default', 'long_form', 'landing']],
            'message' => 'Template must be default, long_form, or landing.',
        ]);

        $validator->add('visibility', 'inEnum', [
            'rule' => ['inList', ['public']],
            'message' => 'Visibility must be public.',
        ]);

        $validator
            ->requirePresence('author_id', 'create')
            ->integer('author_id');

        $validator->allowEmptyDateTime('published_at');
        $validator->allowEmptyString('parent_id')->integer('parent_id');
        $validator->nonNegativeInteger('position');
        $validator->allowEmptyString('body');
        $validator->add('data', 'shape', [
            'rule' => is_array(...),
            'message' => 'Custom field data must be a key/value map.',
        ]);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug', 'parent_id', 'workspace_id'], ['message' => 'Slug must be unique within siblings.', 'allowMultipleNulls' => false]), ['errorField' => 'slug']);
        $rules->add($rules->existsIn(['author_id'], 'Authors'), ['errorField' => 'author_id']);
        $rules->add($rules->existsIn(['parent_id'], 'ParentPages', ['allowNullableNulls' => true]), ['errorField' => 'parent_id']);

        $rules->add(function (Page $entity): bool {
            if ($entity->id === null) {
                return true;
            }

            $ancestorIds = array_map(static fn (Page $page): int => $page->id, $this->ancestorsOf($entity->parent_id));

            return !in_array($entity->id, $ancestorIds, true);
        }, 'cycleCheck', ['errorField' => 'parent_id', 'message' => 'A page cannot be its own ancestor.']);

        return $rules;
    }

    /**
     * @param SelectQuery<Page> $query
     * @return SelectQuery<Page>
     */
    public function findLive(SelectQuery $query): SelectQuery
    {
        return $query->where(['Pages.status' => PostStatus::Live->value]);
    }

    /**
     * @param SelectQuery<Page> $query
     * @param string|array<int, string> $status
     * @return SelectQuery<Page>
     */
    public function findByStatus(SelectQuery $query, string|array $status): SelectQuery
    {
        return $query->where(['Pages.status IN' => (array) $status]);
    }

    /**
     * @param SelectQuery<Page> $query
     * @return SelectQuery<Page>
     */
    public function findByAuthor(SelectQuery $query, int $authorId): SelectQuery
    {
        return $query->where(['Pages.author_id' => $authorId]);
    }

    /**
     * @param SelectQuery<Page> $query
     * @return SelectQuery<Page>
     */
    public function findByParent(SelectQuery $query, int $parentId): SelectQuery
    {
        return $query->where(['Pages.parent_id' => $parentId]);
    }

    public function findPublicByPath(string $path, bool $includeUnpublished = false): ?Page
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $s): bool => $s !== ''));
        if ($segments === []) {
            return null;
        }

        $parentId = null;
        $current = null;
        foreach ($segments as $segment) {
            $conditions = ['Pages.slug' => $segment, 'Pages.parent_id IS' => $parentId];
            if (!$includeUnpublished) {
                $conditions['Pages.status'] = PostStatus::Live->value;
            }

            $current = $this->find()->where($conditions)->first();
            if ($current === null) {
                return null;
            }
            $parentId = $current->id;
        }

        return $current;
    }

    /**
     * @return list<Page>
     */
    public function ancestorsOf(?int $parentId): array
    {
        if ($parentId === null) {
            return [];
        }

        return $this->chainOf($parentId, $this->outline());
    }

    /**
     * @return array<int, string>
     */
    public function pathsById(): array
    {
        $pages = $this->outline();

        return array_map(function (Page $page) use ($pages): string {
            $chain = $this->chainOf($page->id, $pages);

            return implode('/', array_map(static fn (Page $node): string => $node->slug, $chain));
        }, $pages);
    }

    /**
     * @param array<int, Page> $pages
     * @return list<Page>
     */
    private function chainOf(int $id, array $pages): array
    {
        $chain = [];
        $cursor = $id;
        while ($cursor !== null && isset($pages[$cursor]) && !isset($chain[$cursor])) {
            $chain[$cursor] = $pages[$cursor];
            $cursor = $pages[$cursor]->parent_id;
        }

        return array_reverse(array_values($chain));
    }

    /**
     * @return array<int, Page>
     */
    private function outline(): array
    {
        $pages = [];
        foreach ($this->find()->select(['id', 'title', 'slug', 'parent_id'])->all() as $page) {
            $pages[$page->id] = $page;
        }

        return $pages;
    }
}
