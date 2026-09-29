<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property DateTime $created
 * @property DateTime $modified
 * @property array<Membership> $memberships
 */
final class Workspace extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'slug' => true,
    ];
}
