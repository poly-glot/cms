<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use App\Model\Enum\UserRole;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;

final class UserMailer extends Mailer
{
    private const string SITE_NAME = 'Cabinet';

    public function invite(User $user, string $token, UserRole $role): void
    {
        $this
            ->setTo((string) $user->email)
            ->setSubject('You have been invited to ' . self::SITE_NAME)
            ->setViewVars([
                'user' => $user,
                'role' => $role,
                'link' => $this->link('/users/set-password/' . $token),
                'siteName' => self::SITE_NAME,
            ]);
    }

    public function resetPassword(User $user, string $token): void
    {
        $this
            ->setTo((string) $user->email)
            ->setSubject(sprintf('Reset your %s password', self::SITE_NAME))
            ->setViewVars([
                'user' => $user,
                'link' => $this->link('/reset-password/' . $token),
                'siteName' => self::SITE_NAME,
            ]);

        $this->viewBuilder()->setTemplate('reset');
    }

    private function link(string $path): string
    {
        $base = Configure::read('App.fullBaseUrl', 'http://localhost');

        return rtrim(is_string($base) ? $base : 'http://localhost', '/') . $path;
    }
}
