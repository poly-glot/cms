<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Enum\UserRole;
use Authorization\IdentityInterface;
use Cake\Datasource\EntityInterface;

final class EditorialPolicy
{
    public function canAdd(IdentityInterface $user, EntityInterface $resource): bool
    {
        return UserRole::current()->isEditorial();
    }

    public function canEdit(IdentityInterface $user, EntityInterface $resource): bool
    {
        return UserRole::current()->isEditorial();
    }

    public function canDelete(IdentityInterface $user, EntityInterface $resource): bool
    {
        return UserRole::current()->isEditorial();
    }

    public function canModerate(IdentityInterface $user, EntityInterface $resource): bool
    {
        return UserRole::current()->isEditorial();
    }

    public function canManage(IdentityInterface $user, EntityInterface $resource): bool
    {
        return UserRole::current()->isEditorial();
    }
}
