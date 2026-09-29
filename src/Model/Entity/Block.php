<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Concern\HasFieldData;
use App\Model\Enum\BlockType;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $block_type
 * @property string $name
 * @property array<string, mixed> $data
 * @property int $author_id
 * @property DateTime $created
 * @property DateTime $modified
 * @property User|null $author
 * @property array<int, Page> $pages
 */
final class Block extends Entity
{
    use HasFieldData;

    protected array $_accessible = [
        'block_type' => true,
        'name' => true,
        'data' => true,
    ];

    public BlockType $type {
        get => BlockType::from((string) $this->block_type);
    }
}
