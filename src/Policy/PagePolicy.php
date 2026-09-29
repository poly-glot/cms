<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Page;
use Authorization\IdentityInterface;

final class PagePolicy extends AuthoredContentPolicy
{
    public function canRestore(IdentityInterface $user, Page $page): bool
    {
        return $this->editorialOrOwner($user, $page);
    }

    public function canAttachTag(IdentityInterface $user, Page $page): bool
    {
        return $this->editorialOrOwner($user, $page);
    }

    public function canDetachTag(IdentityInterface $user, Page $page): bool
    {
        return $this->editorialOrOwner($user, $page);
    }
}
