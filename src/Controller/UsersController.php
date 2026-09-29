<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\User\InvitationService;
use App\Service\User\PasswordResetService;
use Cake\Http\Response;
use DomainException;
use Override;

final class UsersController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['login', 'setPassword', 'forgotPassword', 'resetPassword']);
        $this->viewBuilder()->setLayout('auth');
    }

    public function login(): ?Response
    {
        $result = $this->Authentication->getResult();

        if ($result?->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/';

            return $this->redirect($target);
        }

        if ($this->request->is('post') && $result !== null && !$result->isValid()) {
            $this->Flash->error('Email or password is incorrect.');
        }

        return null;
    }

    public function logout(): ?Response
    {
        $this->Authentication->logout();

        return $this->redirect('/login');
    }

    public function setPassword(string $token): ?Response
    {
        if ($this->request->is('post')) {
            $password = $this->request->getData('password');
            try {
                new InvitationService()->accept(
                    token: $token,
                    password: is_string($password) ? $password : '',
                );
                $this->Flash->success('Password set. Please sign in.');

                return $this->redirect('/login');
            } catch (DomainException $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        $this->set('token', $token);

        return null;
    }

    public function forgotPassword(): ?Response
    {
        if ($this->request->is('post')) {
            $email = $this->request->getData('email');
            new PasswordResetService()->request(is_string($email) ? $email : '');
            $this->Flash->success('If an account exists for that email, a reset link is on its way.');

            return $this->redirect('/login');
        }

        return null;
    }

    public function resetPassword(string $token): ?Response
    {
        if ($this->request->is('post')) {
            $password = $this->request->getData('password');
            try {
                new PasswordResetService()->reset(
                    token: $token,
                    password: is_string($password) ? $password : '',
                );
                $this->Flash->success('Password reset. Please sign in.');

                return $this->redirect('/login');
            } catch (DomainException $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        $this->set('token', $token);

        return null;
    }
}
