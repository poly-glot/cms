<?php

declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Tenancy\TenantContext;
use Authentication\IdentityInterface;
use Cake\View\Cell;
use Cake\View\View;

/**
 * @extends Cell<View>
 */
final class WorkspaceSwitcherCell extends Cell
{
    public function display(): void
    {
        $identity = $this->request->getAttribute('identity');
        $memberships = [];
        if ($identity instanceof IdentityInterface) {
            $userId = $identity->getIdentifier();
            if (is_int($userId)) {
                $memberships = $this->fetchTable('Memberships')
                    ->find('forUser', userId: $userId)
                    ->all()
                    ->toList();
            }
        }

        $this->set('memberships', $memberships);
        $this->set('activeWorkspaceId', TenantContext::instance()->workspaceId);
    }
}
