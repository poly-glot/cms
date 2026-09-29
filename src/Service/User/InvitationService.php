<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Mailer\UserMailer;
use App\Model\Entity\Membership;
use App\Model\Entity\User;
use App\Model\Enum\UserRole;
use App\Model\Table\MembershipsTable;
use App\Model\Tenancy\TenantContext;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use DomainException;

final class InvitationService
{
    use LocatorAwareTrait;

    public const int EXPIRY_HOURS = 168;

    /**
     * @return array{user: User, membership: Membership, token: string|null, isNewUser: bool}
     */
    public function invite(string $name, string $email, string $role): array
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $users = $this->fetchTable('Users');
        /** @var MembershipsTable $memberships */
        $memberships = $this->fetchTable('Memberships');

        $connection = $users->getConnection();

        /** @var array{user: User, membership: Membership, token: string|null, isNewUser: bool} $result */
        $result = $connection->transactional(function () use ($users, $memberships, $name, $email, $role, $workspaceId): array {
            $existing = $users->find()->where(['Users.email' => $email])->first();

            if ($existing instanceof User) {
                $membership = $this->createMembership($memberships, $existing->id, $workspaceId, $role);

                return ['user' => $existing, 'membership' => $membership, 'token' => null, 'isNewUser' => false];
            }

            $token = bin2hex(random_bytes(32));
            $now = DateTime::now();
            $user = $users->newEntity(
                ['name' => $name, 'email' => $email],
                ['validate' => 'invite', 'fields' => ['name', 'email']],
            );
            $user->invitation_token_hash = hash('sha256', $token);
            $user->invitation_expires_at = $now->addHours(self::EXPIRY_HOURS);
            $user->invited_at = $now;
            $user->accepted_at = null;
            $users->saveOrFail($user);

            $membership = $this->createMembership($memberships, $user->id, $workspaceId, $role);

            return ['user' => $user, 'membership' => $membership, 'token' => $token, 'isNewUser' => true];
        });

        if ($result['isNewUser'] && $result['token'] !== null) {
            new UserMailer()->send('invite', [
                $result['user'],
                $result['token'],
                UserRole::fromValue($result['membership']->role),
            ]);
        }

        return $result;
    }

    public function accept(string $token, string $password): User
    {
        if (strlen($password) < 8) {
            throw new DomainException('Password must be at least eight characters.');
        }
        $users = $this->fetchTable('Users');
        $user = $users->find()
            ->where(['invitation_token_hash' => hash('sha256', $token)])
            ->first();
        if ($user === null) {
            throw new DomainException('This invite link is no longer valid.');
        }
        if ($user->accepted_at !== null) {
            throw new DomainException('This invite has already been used.');
        }
        if ($user->invitation_expires_at === null || $user->invitation_expires_at->isPast()) {
            throw new DomainException('This invite has expired.');
        }

        $user->password = $password;
        $user->invitation_token_hash = null;
        $user->invitation_expires_at = null;
        $user->accepted_at = DateTime::now();
        $users->saveOrFail($user);

        return $user;
    }

    private function createMembership(MembershipsTable $memberships, int $userId, int $workspaceId, string $role): Membership
    {
        $membership = $memberships->newEntity(['role' => $role]);
        $membership->workspace_id = $workspaceId;
        $membership->user_id = $userId;

        if (!$memberships->save($membership)) {
            $errors = $membership->getErrors();
            $message = 'Could not create the membership.';
            if (isset($errors['user_id']) && is_array($errors['user_id'])) {
                $first = reset($errors['user_id']);
                $message = is_string($first) ? $first : $message;
            }
            throw new DomainException($message);
        }

        return $membership;
    }
}
