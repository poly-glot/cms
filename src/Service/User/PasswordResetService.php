<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Mailer\UserMailer;
use App\Model\Entity\User;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use DomainException;

/**
 * Issues single-use password-reset links and applies them. Tokens are stored
 * as SHA-256 hashes so a database read alone cannot recover the emailed link.
 *
 * request() must not reveal whether an address is registered. It therefore
 * looks the address up by email alone (no password filter that would set
 * invited accounts apart), always pays the token-generation cost, and pads
 * every call to a fixed floor so its latency does not correlate with account
 * existence even though the mail send only happens for a resettable user.
 */
final class PasswordResetService
{
    use LocatorAwareTrait;

    public const int EXPIRY_HOURS = 1;

    public const int MIN_RESPONSE_MS = 300;

    public function request(string $email): void
    {
        $startedAt = microtime(true);

        $users = $this->fetchTable('Users');
        $user = $users->find()->where(['email' => $email])->first();

        $token = bin2hex(random_bytes(32));

        if ($user instanceof User && $user->password !== null) {
            $user->password_reset_token_hash = hash('sha256', $token);
            $user->password_reset_expires_at = DateTime::now()->addHours(self::EXPIRY_HOURS);
            $users->saveOrFail($user);

            new UserMailer()->send('resetPassword', [$user, $token]);
        }

        self::padToFloor($startedAt);
    }

    private static function padToFloor(float $startedAt): void
    {
        $remainingMicros = (int) ((self::MIN_RESPONSE_MS / 1000 - (microtime(true) - $startedAt)) * 1_000_000);
        if ($remainingMicros > 0) {
            usleep($remainingMicros);
        }
    }

    public function reset(string $token, string $password): User
    {
        if (strlen($password) < 8) {
            throw new DomainException('Password must be at least eight characters.');
        }
        $users = $this->fetchTable('Users');
        $user = $users->find()
            ->where(['password_reset_token_hash' => hash('sha256', $token)])
            ->first();
        if ($user === null) {
            throw new DomainException('This reset link is no longer valid.');
        }
        if ($user->password_reset_expires_at === null || $user->password_reset_expires_at->isPast()) {
            throw new DomainException('This reset link has expired.');
        }

        $user->password = $password;
        $user->password_reset_token_hash = null;
        $user->password_reset_expires_at = null;
        $users->saveOrFail($user);

        return $user;
    }
}
