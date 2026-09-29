<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Media;
use App\Model\Enum\UserRole;
use Authorization\IdentityInterface;

final class MediaPolicy
{
    public function canView(IdentityInterface $user, Media $media): bool
    {
        return true;
    }

    public function canUpdate(IdentityInterface $user, Media $media): bool
    {
        return true;
    }

    public function canDelete(IdentityInterface $user, Media $media): bool
    {
        return UserRole::current()->isEditorial();
    }
}
