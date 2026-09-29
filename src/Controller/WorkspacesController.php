<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\WorkspaceProvisionException;
use App\Model\Entity\Membership;
use App\Service\Workspace\WorkspaceProvisioner;
use Authentication\IdentityInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Response;
use Override;

final class WorkspacesController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['home']);
        $this->viewBuilder()->setLayout('auth');
    }

    public function add(): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity instanceof IdentityInterface) {
            return $this->redirect('/login') ?? throw new InternalErrorException('Redirect failed.');
        }

        $ownerId = $identity->getIdentifier();
        if (!is_int($ownerId)) {
            throw new InternalErrorException('Authenticated identity has no integer identifier.');
        }

        if (!$this->request->is('post')) {
            return null;
        }

        $payload = ['name' => $this->stringInput('name'), 'slug' => $this->stringInput('slug')];
        $this->set('form', $payload);

        try {
            $result = new WorkspaceProvisioner()->provision($ownerId, $payload['name'], $payload['slug']);
        } catch (WorkspaceProvisionException $e) {
            $this->set('errors', $e->errors);
            $this->Flash->error('Please correct the highlighted fields.');

            return null;
        }

        $this->Flash->success(sprintf('Workspace “%s” is ready.', $result['workspace']->name));

        $target = $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'Dashboard',
            'action' => 'index',
            'workspaceSlug' => $result['workspace']->slug,
        ]);

        return $target ?? throw new InternalErrorException('Redirect failed.');
    }

    public function home(): Response
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity instanceof IdentityInterface) {
            return $this->redirect('/login') ?? throw new InternalErrorException('Redirect failed.');
        }

        $userId = $identity->getIdentifier();
        if (!is_int($userId)) {
            throw new InternalErrorException('Authenticated identity has no integer identifier.');
        }

        $membership = $this->fetchTable('Memberships')
            ->find('forUser', userId: $userId)
            ->first();

        if (!$membership instanceof Membership) {
            $this->Flash->error('You are not yet a member of any workspace.');

            return $this->redirect('/login') ?? throw new InternalErrorException('Redirect failed.');
        }

        $target = $this->redirect(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index', 'workspaceSlug' => $membership->workspace->slug]);

        return $target ?? throw new InternalErrorException('Redirect failed.');
    }
}
