<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Exception\WorkspaceProvisionException;
use App\Model\Entity\Membership;
use App\Model\Entity\User;
use App\Model\Entity\Workspace;
use App\Service\Workspace\WorkspaceProvisioner;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

final class SignupService
{
    use LocatorAwareTrait;

    /**
     * @return array{user: User, workspace: Workspace, membership: Membership}
     */
    public function signup(string $email, string $password, string $name, string $workspaceName, string $workspaceSlug): array
    {
        $users = $this->fetchTable('Users');

        try {
            /** @var array{user: User, workspace: Workspace, membership: Membership} $result */
            $result = $users->getConnection()->transactional(static function () use ($users, $email, $password, $name, $workspaceName, $workspaceSlug): array {
                $user = $users->newEntity([
                    'email' => $email,
                    'password' => $password,
                    'name' => $name,
                ]);
                $user->accepted_at = DateTime::now();
                if (!$users->save($user)) {
                    throw new SignupValidationException($user->getErrors(), 'user');
                }

                return ['user' => $user] + new WorkspaceProvisioner()->provision($user->id, $workspaceName, $workspaceSlug);
            });
        } catch (WorkspaceProvisionException $exception) {
            throw new SignupValidationException($exception->errors, 'workspace');
        }

        return $result;
    }
}
