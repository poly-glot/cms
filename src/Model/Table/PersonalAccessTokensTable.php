<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\PersonalAccessToken;
use App\Model\Enum\TokenScope;
use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, PersonalAccessToken>
 */
final class PersonalAccessTokensTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Workspaces', ['joinType' => 'INNER']);
        $this->belongsTo('Users', ['joinType' => 'INNER']);
    }

    /**
     * @param list<TokenScope> $scopes
     * @return array{token: string, entity: PersonalAccessToken}
     */
    public function issue(int $workspaceId, int $userId, string $name, array $scopes, ?DateTime $expiresAt): array
    {
        $plain = bin2hex(random_bytes(32));

        $entity = $this->newEntity([
            'name' => $name,
            'scopes' => array_map(static fn (TokenScope $scope): string => $scope->value, $scopes),
            'expires_at' => $expiresAt,
        ]);
        $entity->set('token_hash', hash('sha256', $plain));
        $entity->set('workspace_id', $workspaceId);
        $entity->set('user_id', $userId);

        $this->saveOrFail($entity);

        return ['token' => $plain, 'entity' => $entity];
    }

    public function resolve(string $plain): ?PersonalAccessToken
    {
        $token = $this->findByTokenHash(hash('sha256', $plain))
            ->contain('Users')
            ->first();

        if (!$token instanceof PersonalAccessToken) {
            return null;
        }
        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return null;
        }

        $token->last_used_at = DateTime::now();
        $this->save($token);

        return $token;
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->maxLength('name', 120);

        $scopes = array_map(static fn (TokenScope $scope): string => $scope->value, TokenScope::cases());
        $validator->multipleOptions('scopes', ['in' => $scopes], 'Choose at least one valid scope.');

        $validator->allowEmptyDateTime('expires_at');

        return $validator;
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['workspace_id'], 'Workspaces'), ['errorField' => 'workspace_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->isUnique(['token_hash'], 'That token already exists.'), ['errorField' => 'token_hash']);

        return $rules;
    }
}
