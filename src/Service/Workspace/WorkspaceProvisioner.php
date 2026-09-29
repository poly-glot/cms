<?php

declare(strict_types=1);

namespace App\Service\Workspace;

use App\Exception\WorkspaceProvisionException;
use App\Model\Entity\Membership;
use App\Model\Entity\Workspace;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use Cake\ORM\Locator\LocatorAwareTrait;

final class WorkspaceProvisioner
{
    use LocatorAwareTrait;

    /**
     * @return array{workspace: Workspace, membership: Membership}
     */
    public function provision(int $ownerId, string $name, string $slug): array
    {
        $workspaces = $this->fetchTable('Workspaces');
        $memberships = $this->fetchTable('Memberships');
        $connection = $workspaces->getConnection();

        /** @var array{workspace: Workspace, membership: Membership} $result */
        $result = $connection->transactional(static function () use ($workspaces, $memberships, $ownerId, $name, $slug): array {
            $workspace = $workspaces->newEntity(['name' => $name, 'slug' => $slug]);
            if (!$workspaces->save($workspace)) {
                throw new WorkspaceProvisionException($workspace->getErrors());
            }

            $membership = $memberships->newEntity(['role' => UserRole::Admin->value]);
            $membership->workspace_id = $workspace->id;
            $membership->user_id = $ownerId;

            $savedMembership = TenantContext::instance()->runScoped(
                $workspace->id,
                UserRole::Admin,
                static fn (): Membership|false => $memberships->save($membership),
                workspaceSlug: $workspace->slug,
            );

            if ($savedMembership === false) {
                throw new WorkspaceProvisionException($membership->getErrors());
            }

            return ['workspace' => $workspace, 'membership' => $savedMembership];
        });

        return $result;
    }
}
