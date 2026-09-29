<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Enum\UserRole;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

/**
 * Restricts a whole admin controller to administrators. Unauthenticated
 * requests are left to the authentication redirect; an authenticated
 * non-admin gets a 403.
 */
trait AdminOnlyTrait
{
    public function beforeFilter(EventInterface $event): void
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity !== null && !UserRole::current()->isAdmin()) {
            throw new ForbiddenException('Administrators only.');
        }
    }
}
