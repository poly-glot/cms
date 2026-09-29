<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property int $position
 * @property string $title
 * @property string $type
 * @property int|null $page_id
 * @property string|null $url
 * @property string $target
 * @property DateTime $created
 * @property DateTime $modified
 * @property Page|null $page
 * @property array<MenuItem> $children
 */
final class MenuItem extends Entity
{
    protected array $_accessible = [
        'menu_id' => true,
        'parent_id' => true,
        'position' => true,
        'title' => true,
        'type' => true,
        'page_id' => true,
        'url' => true,
        'target' => true,
        'children' => true,
    ];

    public bool $opensInNewWindow {
        get => $this->target === '_blank';
    }
}
