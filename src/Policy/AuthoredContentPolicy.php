<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\CollectionEntry;
use App\Model\Entity\Page;
use App\Model\Entity\Post;
use App\Model\Enum\UserRole;
use Authorization\IdentityInterface;

class AuthoredContentPolicy
{
    public function canAdd(IdentityInterface $user, Page|Post|CollectionEntry $content): bool
    {
        return true;
    }

    public function canEdit(IdentityInterface $user, Page|Post|CollectionEntry $content): bool
    {
        return $this->editorialOrOwner($user, $content);
    }

    public function canSaveDraft(IdentityInterface $user, Page|Post|CollectionEntry $content): bool
    {
        return $this->editorialOrOwner($user, $content);
    }

    public function canDelete(IdentityInterface $user, Page|Post|CollectionEntry $content): bool
    {
        return $this->editorialOrOwner($user, $content);
    }

    public function canPublish(IdentityInterface $user, Page|Post|CollectionEntry $content): bool
    {
        return UserRole::current()->isEditorial();
    }

    public function canSchedule(IdentityInterface $user, Page|Post|CollectionEntry $content): bool
    {
        return UserRole::current()->isEditorial();
    }

    protected function editorialOrOwner(IdentityInterface $user, Page|Post|CollectionEntry $content): bool
    {
        if (UserRole::current()->isEditorial()) {
            return true;
        }

        $id = $user['id'] ?? null;

        return is_numeric($id) && (int) $id === $content->author_id;
    }
}
