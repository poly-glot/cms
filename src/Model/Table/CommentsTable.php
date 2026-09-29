<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Comment;
use App\Model\Enum\CommentStatus;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Comment>
 */
final class CommentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->setDisplayField('author_name');
        $this->addBehavior('Timestamp');

        $this->belongsTo('ParentComments', [
            'className' => 'Comments',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('Children', [
            'className' => 'Comments',
            'foreignKey' => 'parent_id',
            'dependent' => true,
        ]);
        $this->belongsTo('Pages', [
            'foreignKey' => 'commentable_id',
            'conditions' => ['Comments.commentable_type' => 'Pages'],
        ]);
        $this->belongsTo('Posts', [
            'foreignKey' => 'commentable_id',
            'conditions' => ['Comments.commentable_type' => 'Posts'],
        ]);
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('author_name', 'create')
            ->notEmptyString('author_name', 'Please add your name.')
            ->maxLength('author_name', 120);

        $validator
            ->allowEmptyString('author_email')
            ->email('author_email', false, 'Please use a valid email address.')
            ->maxLength('author_email', 255);

        $validator
            ->requirePresence('body', 'create')
            ->notEmptyString('body', 'A comment cannot be empty.')
            ->maxLength('body', 5000);

        return $validator;
    }

    private const array COMMENTABLE_TABLES = ['Pages' => 'Pages', 'Posts' => 'Posts'];

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(static function (Comment $entity): bool {
            $table = self::COMMENTABLE_TABLES[$entity->commentable_type] ?? null;
            if ($table === null) {
                return false;
            }

            return TableRegistry::getTableLocator()->get($table)->exists(['id' => $entity->commentable_id]);
        }, 'commentableExists', [
            'errorField' => 'commentable_id',
            'message' => 'The target page or post does not exist.',
        ]);

        $rules->add(
            $rules->existsIn(['parent_id'], 'ParentComments', 'The comment being replied to no longer exists.'),
            ['errorField' => 'parent_id', 'allowNullableNulls' => true],
        );

        return $rules;
    }

    /**
     * @param SelectQuery<Comment> $query
     * @return SelectQuery<Comment>
     */
    public function findForModeration(SelectQuery $query): SelectQuery
    {
        return $query
            ->where(['Comments.parent_id IS' => null])
            ->contain([
                'Children' => $this->approvedChildren(...),
                'Pages' => ['fields' => ['Pages.id', 'Pages.title']],
                'Posts' => ['fields' => ['Posts.id', 'Posts.title']],
            ])
            ->orderByDesc('Comments.created');
    }

    /**
     * @param SelectQuery<Comment> $query
     * @return SelectQuery<Comment>
     */
    public function findApprovedFor(SelectQuery $query, string $commentableType, int $commentableId): SelectQuery
    {
        return $query
            ->where([
                'Comments.commentable_type' => $commentableType,
                'Comments.commentable_id' => $commentableId,
                'Comments.parent_id IS' => null,
                'Comments.status' => CommentStatus::Approved->value,
            ])
            ->contain(['Children' => $this->approvedChildren(...)])
            ->orderByAsc('Comments.created');
    }

    public function unreadCount(): int
    {
        return $this->find()->where(['Comments.is_read' => false])->count();
    }

    /**
     * @param SelectQuery<Comment> $query
     * @return SelectQuery<Comment>
     */
    private function approvedChildren(SelectQuery $query): SelectQuery
    {
        return $query->where(['Children.status' => CommentStatus::Approved->value])->orderByAsc('Children.created');
    }
}
