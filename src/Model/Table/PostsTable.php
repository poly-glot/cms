<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Behavior\EditorialContentBehavior;
use App\Model\Entity\Post;
use App\Model\Enum\PostStatus;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{EditorialContent: EditorialContentBehavior}, Post>
 */
final class PostsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');
        $this->addBehavior('EditorialContent', ['noun' => 'posts', 'slugFromTitle' => true]);

        $this->belongsTo('Authors', [
            'className' => 'Users',
            'joinType' => 'INNER',
        ]);
        $this->belongsToMany('Tags', [
            'through' => 'Taggables',
            'foreignKey' => 'taggable_id',
            'conditions' => ['Taggables.taggable_type' => 'Posts'],
        ]);
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
            ->requirePresence('status', 'create')
            ->enum('status', PostStatus::class, 'Status must be draft, live, or scheduled.');

        $validator
            ->requirePresence('author_id', 'create')
            ->integer('author_id');

        $validator->allowEmptyString('excerpt')->maxLength('excerpt', 500);
        $validator->allowEmptyString('body');
        $validator->allowEmptyDateTime('published_at');
        $validator->boolean('comments_enabled');
        $validator->add('data', 'shape', [
            'rule' => is_array(...),
            'message' => 'Custom field data must be a key/value map.',
        ]);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug', 'workspace_id'], 'Slug must be unique.'), ['errorField' => 'slug']);
        $rules->add($rules->existsIn(['author_id'], 'Authors'), ['errorField' => 'author_id']);

        return $rules;
    }

    /**
     * @param SelectQuery<Post> $query
     * @return SelectQuery<Post>
     */
    public function findLive(SelectQuery $query): SelectQuery
    {
        return $query->where(['Posts.status' => PostStatus::Live->value]);
    }

    /**
     * @param SelectQuery<Post> $query
     * @param string|array<int, string> $status
     * @return SelectQuery<Post>
     */
    public function findByStatus(SelectQuery $query, string|array $status): SelectQuery
    {
        return $query->where(['Posts.status IN' => (array) $status]);
    }

    /**
     * @param SelectQuery<Post> $query
     * @return SelectQuery<Post>
     */
    public function findByAuthor(SelectQuery $query, int $authorId): SelectQuery
    {
        return $query->where(['Posts.author_id' => $authorId]);
    }

    /**
     * @param SelectQuery<Post> $query
     * @return SelectQuery<Post>
     */
    public function findSearch(SelectQuery $query, string $keyword): SelectQuery
    {
        $term = trim($keyword);
        if ($term === '') {
            return $query;
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(['OR' => [
            'Posts.title LIKE' => $like,
            'Posts.slug LIKE' => $like,
        ]]);
    }
}
