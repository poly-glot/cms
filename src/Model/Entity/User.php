<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $email
 * @property string|null $password
 * @property string $name
 * @property string|null $invitation_token_hash
 * @property DateTime|null $invitation_expires_at
 * @property DateTime|null $invited_at
 * @property DateTime|null $accepted_at
 * @property string|null $password_reset_token_hash
 * @property DateTime|null $password_reset_expires_at
 * @property DateTime $created
 * @property DateTime $modified
 */
final class User extends Entity
{
    private const array AVATAR_PALETTE = [
        ['#e6c79c', '#a07238'],
        ['#b9d0e2', '#3f6a8c'],
        ['#d8c6a3', '#8a6b3d'],
        ['#cfd9c0', '#5f7a4f'],
        ['#e4bfb6', '#9a5446'],
        ['#c9c2d8', '#5e5184'],
    ];

    protected array $_accessible = [
        'email' => true,
        'password' => true,
        'name' => true,
    ];

    protected array $_hidden = ['password', 'password_reset_token_hash'];

    public string $avatarStyle {
        get => self::avatarStyleFor((string) $this->name);
    }

    public static function avatarStyleFor(string $seed): string
    {
        $index = abs(crc32($seed)) % count(self::AVATAR_PALETTE);
        [$light, $dark] = self::AVATAR_PALETTE[$index];

        return sprintf('--c1:%s; --c2:%s;', $light, $dark);
    }

    protected function _setPassword(string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }

        return new DefaultPasswordHasher()->hash($value);
    }
}
