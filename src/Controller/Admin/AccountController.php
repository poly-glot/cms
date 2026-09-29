<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\User;
use Cake\Http\Response;
use DomainException;

final class AccountController extends AdminController
{
    public function index(): ?Response
    {
        $users = $this->fetchTable('Users');
        $user = $this->currentUser();
        $this->Authorization->authorize($user, 'editAccount');

        if ($this->request->is('post')) {
            $email = $this->stringInput('email');
            $emailChanging = $email !== '' && $email !== $user->email;

            if ($emailChanging && !$users->verifyPassword($user, $this->stringInput('current_password'))) {
                $this->Flash->error('Enter your current password to change your email address.');
                $this->set('user', $user);

                return null;
            }

            $user = $users->patchEntity($user, [
                'name' => $this->stringInput('name'),
                'email' => $email,
            ]);

            if ($users->save($user)) {
                $this->Flash->success('Your profile has been updated.');

                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error('Please correct the errors below.');
        }

        $this->set('user', $user);

        return null;
    }

    public function password(): ?Response
    {
        $this->request->allowMethod('post');
        $user = $this->currentUser();
        $this->Authorization->authorize($user, 'editAccount');

        if ($this->stringInput('new_password') !== $this->stringInput('new_password_confirm')) {
            $this->Flash->error('The new passwords do not match.');

            return $this->redirect(['action' => 'index']);
        }

        try {
            $this->fetchTable('Users')->changePassword(
                $user,
                $this->stringInput('current_password'),
                $this->stringInput('new_password'),
            );
            $this->request->getSession()->renew();
            $this->Flash->success('Your password has been changed.');
        } catch (DomainException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    private function currentUser(): User
    {
        return $this->fetchTable('Users')->get($this->currentUserId());
    }
}
