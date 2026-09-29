<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\User\SignupService;
use App\Service\User\SignupValidationException;
use Authentication\Identity;
use Authentication\IdentityInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Response;
use Override;

final class SignupController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['index']);
        $this->viewBuilder()->setLayout('auth');
    }

    public function index(): ?Response
    {
        if ($this->Authentication->getIdentity() instanceof IdentityInterface) {
            return $this->redirect('/');
        }

        if (!$this->request->is('post')) {
            return null;
        }

        $payload = [
            'email' => $this->stringInput('email'),
            'password' => $this->stringInput('password'),
            'name' => $this->stringInput('name'),
            'workspaceName' => $this->stringInput('workspace_name'),
            'workspaceSlug' => $this->stringInput('workspace_slug'),
        ];
        $this->set('form', $payload);

        try {
            $result = new SignupService()->signup(
                email: $payload['email'],
                password: $payload['password'],
                name: $payload['name'],
                workspaceName: $payload['workspaceName'],
                workspaceSlug: $payload['workspaceSlug'],
            );
        } catch (SignupValidationException $e) {
            $this->set('errors', [$e->scope => $e->errors]);
            $this->Flash->error('Please correct the highlighted fields.');

            return null;
        }

        $this->Authentication->setIdentity(new Identity($result['user']->toArray()));

        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'Dashboard',
            'action' => 'index',
            'workspaceSlug' => $result['workspace']->slug,
        ]) ?? throw new InternalErrorException('Redirect failed after signup.');
    }
}
