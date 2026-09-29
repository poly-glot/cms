<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $slug
 * @property DateTime $created
 * @property DateTime $modified
 * @property array<MenuItem> $menu_items
 */
final class Menu extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'slug' => true,
    ];
}
