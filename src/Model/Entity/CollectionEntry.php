<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Concern\HasFieldData;
use App\Model\Enum\PostStatus;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $collection_id
 * @property string $title
 * @property string $slug
 * @property array<string, mixed> $data
 * @property string $status
 * @property DateTime|null $published_at
 * @property bool $comments_enabled
 * @property int $author_id
 * @property DateTime $created
 * @property DateTime $modified
 * @property Collection|null $collection
 * @property User|null $author
 */
final class CollectionEntry extends Entity
{
    use HasFieldData;

    protected array $_accessible = [
        'collection_id' => true,
        'title' => true,
        'slug' => true,
        'data' => true,
        'status' => true,
        'published_at' => true,
        'comments_enabled' => true,
        'author_id' => false,
    ];

    public PostStatus $statusEnum {
        get => PostStatus::from($this->status);
    }
}
