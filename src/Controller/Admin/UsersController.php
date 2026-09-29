<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Membership;
use App\Model\Entity\User;
use App\Model\Enum\UserRole;
use App\Model\Tenancy\TenantContext;
use App\Service\User\InvitationService;
use App\Service\User\PasswordResetService;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\Exception\PersistenceFailedException;
use DomainException;

final class UsersController extends AdminController
{
    use AdminOnlyTrait;

    public function index(): void
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();

        $this->set([
            'members' => $this->fetchTable('Memberships')->find('rosterFor', workspaceId: $workspaceId)->all(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function editRole(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $membership = $this->membershipOrFail($id);
        $this->Authorization->authorize($membership->user, 'manageRoles');

        $value = $this->request->getData('role');
        $role = is_string($value) ? UserRole::tryFrom($value) : null;
        if ($role === null) {
            $this->Flash->error('Unknown role.');

            return $this->redirect(['action' => 'index']);
        }

        $membership->role = $role->value;
        $this->fetchTable('Memberships')->save($membership)
            ? $this->Flash->success(sprintf('%s is now a %s.', $membership->user->name, $role->label()))
            : $this->Flash->error('Could not update the role.');

        return $this->redirect(['action' => 'index']);
    }

    public function sendReset(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $membership = $this->membershipOrFail($id);
        $this->Authorization->authorize($membership->user, 'sendReset');

        new PasswordResetService()->request($membership->user->email);
        $this->Flash->success(sprintf('A password-reset link is on its way to %s.', $membership->user->name));

        return $this->redirect(['action' => 'index']);
    }

    public function invite(): ?Response
    {
        $users = $this->fetchTable('Users');
        $user = $users->newEmptyEntity();
        $this->Authorization->authorize($user, 'invite');

        if ($this->request->is('post')) {
            $name = $this->request->getData('name');
            $email = $this->request->getData('email');
            $role = $this->request->getData('role');
            try {
                $result = new InvitationService()->invite(
                    name: is_string($name) ? $name : '',
                    email: is_string($email) ? $email : '',
                    role: is_string($role) ? $role : 'author',
                );
                $this->Flash->success($result['isNewUser']
                    ? sprintf('Invited %s. They will receive a link to set their password.', $result['user']->name)
                    : sprintf('%s has been added to the workspace.', $result['user']->name));

                return $this->redirect(['action' => 'index']);
            } catch (PersistenceFailedException $e) {
                /** @var User $user */
                $user = $e->getEntity();
                $this->Flash->error('Please correct the errors below.');
            } catch (DomainException $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        $this->set(['user' => $user, 'roles' => UserRole::cases()]);

        return null;
    }

    private function membershipOrFail(int $id): Membership
    {
        $membership = $this->fetchTable('Memberships')->find()
            ->where(['Memberships.id' => $id, 'Memberships.workspace_id' => TenantContext::instance()->requireWorkspaceId()])
            ->contain('Users')
            ->first();

        return $membership instanceof Membership ? $membership : throw new NotFoundException();
    }
}
