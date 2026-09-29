<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Exception\UnknownWorkspaceException;
use App\Model\Entity\Workspace;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, Workspace>
 */
final class WorkspacesTable extends Table
{
    public const array RESERVED_SLUGS = ['login', 'logout', 'forgot-password', 'reset-password', 'users', 'signup', 'admin', 'workspaces'];

    public const string PRIMARY_SLUG = 'cabinet';

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Timestamp');

        $this->hasMany('Memberships', [
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    /**
     * @param SelectQuery<Workspace> $query
     * @return SelectQuery<Workspace>
     */
    public function findBySlug(SelectQuery $query, string $slug): SelectQuery
    {
        return $query->where(['Workspaces.slug' => $slug]);
    }

    /**
     * @template T
     * @param callable(Workspace): T $work
     * @return T
     */
    public function runAsAdmin(string $slug, callable $work): mixed
    {
        $workspace = $this->find('bySlug', slug: $slug)->first();
        if (!$workspace instanceof Workspace) {
            throw new UnknownWorkspaceException($slug);
        }

        return TenantContext::instance()->runScoped(
            $workspace->id,
            UserRole::Admin,
            static fn () => $work($workspace),
            workspaceSlug: $workspace->slug,
        );
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
            ->regex('slug', '/^[a-z0-9\-]+$/', 'Use lowercase letters, numbers and hyphens only.')
            ->add('slug', 'notReserved', [
                'rule' => static fn (string $value): bool => !in_array($value, self::RESERVED_SLUGS, true),
                'message' => 'That workspace name is reserved. Try another.',
            ]);

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug']), ['errorField' => 'slug']);

        return $rules;
    }
}
