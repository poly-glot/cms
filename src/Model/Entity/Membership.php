<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\UserRole;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $user_id
 * @property string $role
 * @property DateTime $created
 * @property DateTime $modified
 * @property Workspace $workspace
 * @property User $user
 */
final class Membership extends Entity
{
    protected array $_accessible = [
        'workspace_id' => false,
        'user_id' => false,
        'role' => true,
    ];

    public UserRole $roleEnum {
        get => UserRole::fromValue($this->role);
    }
}
