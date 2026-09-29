<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Membership;
use App\Model\Enum\UserRole;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Membership>
 */
final class MembershipsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Timestamp');

        $this->belongsTo('Workspaces', ['joinType' => 'INNER']);
        $this->belongsTo('Users', ['joinType' => 'INNER']);
    }

    /**
     * @param SelectQuery<Membership> $query
     * @return SelectQuery<Membership>
     */
    public function findForUser(SelectQuery $query, int $userId): SelectQuery
    {
        return $query
            ->where(['Memberships.user_id' => $userId])
            ->contain('Workspaces')
            ->orderBy(['Workspaces.name' => 'ASC']);
    }

    /**
     * @param SelectQuery<Membership> $query
     * @return SelectQuery<Membership>
     */
    public function findRosterFor(SelectQuery $query, int $workspaceId): SelectQuery
    {
        return $query
            ->where(['Memberships.workspace_id' => $workspaceId])
            ->contain('Users')
            ->orderBy(['Users.name' => 'ASC']);
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('workspace_id', 'create')
            ->integer('workspace_id');

        $validator
            ->requirePresence('user_id', 'create')
            ->integer('user_id');

        $validator
            ->requirePresence('role', 'create')
            ->enum('role', UserRole::class, 'Choose a valid role.');

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(
            $rules->isUnique(['workspace_id', 'user_id'], 'This user already belongs to the workspace.'),
            ['errorField' => 'user_id'],
        );
        $rules->add($rules->existsIn(['workspace_id'], 'Workspaces'), ['errorField' => 'workspace_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        $rules->addUpdate(
            static fn (EntityInterface $entity): bool => !$entity->isDirty('workspace_id') && !$entity->isDirty('user_id'),
            'membershipPrincipalsImmutable',
            ['errorField' => 'workspace_id', 'message' => 'Membership cannot be reassigned to a different workspace or user.'],
        );

        return $rules;
    }
}
