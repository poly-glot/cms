<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $workspace_id
 * @property int $page_id
 * @property int $block_id
 * @property Page|null $page
 * @property Block|null $block
 */
final class PageBlock extends Entity
{
    protected array $_accessible = [];
}
