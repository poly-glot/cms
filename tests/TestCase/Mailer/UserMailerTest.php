<?php

declare(strict_types=1);

namespace App\Test\TestCase\Mailer;

use App\Mailer\UserMailer;
use App\Model\Entity\User;
use App\Model\Enum\UserRole;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

final class UserMailerTest extends TestCase
{
    use EmailTrait;

    public function testInviteMailCarriesRoleAndSetPasswordLink(): void
    {
        $user = new User(['name' => 'Pat', 'email' => 'pat@example.com']);

        new UserMailer()->send('invite', [$user, 'tok123', UserRole::Editor]);

        $this->assertMailSentTo('pat@example.com');
        $this->assertMailSubjectContains('You have been invited to Cabinet');
        $this->assertMailContainsText(
            "Hi Pat,\n\n"
            . "You've been invited to Cabinet as a Editor.\n\n"
            . "Set your password to finish setting up your account:\n"
            . "http://localhost/users/set-password/tok123\n\n"
            . "This link expires in seven days. If you weren't expecting this, you can ignore it.\n\n"
            . '— Cabinet',
        );
    }

    public function testResetPasswordMailCarriesResetLink(): void
    {
        $user = new User(['name' => 'Pat', 'email' => 'pat@example.com']);

        new UserMailer()->send('resetPassword', [$user, 'tok456']);

        $this->assertMailSentTo('pat@example.com');
        $this->assertMailSubjectContains('Reset your Cabinet password');
        $this->assertMailContainsText(
            "Hi Pat,\n\n"
            . "We received a request to reset your Cabinet password.\n\n"
            . "Choose a new password using the link below:\n"
            . "http://localhost/reset-password/tok456\n\n"
            . "This link expires in one hour. If you didn't request this, you can ignore it.\n\n"
            . '— Cabinet',
        );
    }
}
