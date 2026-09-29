<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use DomainException;
use Override;

/**
 * @extends Table<array{}, User>
 */
final class UsersTable extends Table
{
    public const int MIN_PASSWORD_LENGTH = 8;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Timestamp');
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->email('email')
            ->maxLength('email', 190);

        $validator
            ->requirePresence('password', 'create')
            ->notEmptyString('password')
            ->minLength('password', self::MIN_PASSWORD_LENGTH, sprintf('Password must be at least %d characters.', self::MIN_PASSWORD_LENGTH));

        $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->maxLength('name', 120);

        return $validator;
    }

    public function validationInvite(Validator $validator): Validator
    {
        return $this->validationDefault($validator)->remove('password');
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        return $rules;
    }

    public function verifyPassword(User $user, string $password): bool
    {
        return $password !== ''
            && $user->password !== null
            && new DefaultPasswordHasher()->check($password, $user->password);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!$this->verifyPassword($user, $currentPassword)) {
            throw new DomainException('Your current password is incorrect.');
        }

        if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
            throw new DomainException('Your new password must be at least eight characters.');
        }

        if ($currentPassword === $newPassword) {
            throw new DomainException('Choose a new password that is different from your current one.');
        }

        $user->password = $newPassword;
        $this->saveOrFail($user);
    }
}
