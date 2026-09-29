<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use App\Model\Enum\UserRole;
use Authorization\IdentityInterface;

/**
 * Only administrators manage the roster — assigning roles and sending invites.
 */
final class UserPolicy
{
    public function canManageRoles(IdentityInterface $actor, User $user): bool
    {
        return UserRole::current()->isAdmin();
    }

    public function canInvite(IdentityInterface $actor, User $user): bool
    {
        return UserRole::current()->isAdmin();
    }

    public function canSendReset(IdentityInterface $actor, User $user): bool
    {
        return UserRole::current()->isAdmin();
    }

    public function canEditAccount(IdentityInterface $actor, User $user): bool
    {
        $actorId = $actor->getOriginalData()['id'] ?? null;

        return is_numeric($actorId) && (int) $actorId === $user->id;
    }
}
