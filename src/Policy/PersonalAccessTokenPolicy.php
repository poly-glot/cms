<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\PersonalAccessToken;
use App\Model\Enum\UserRole;
use Authorization\IdentityInterface;

/**
 * Only administrators mint and revoke a workspace's API tokens.
 */
final class PersonalAccessTokenPolicy
{
    public function canCreate(IdentityInterface $actor, PersonalAccessToken $token): bool
    {
        return UserRole::current()->isAdmin();
    }

    public function canRevoke(IdentityInterface $actor, PersonalAccessToken $token): bool
    {
        return UserRole::current()->isAdmin();
    }
}
