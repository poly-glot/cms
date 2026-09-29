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
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string|null $body
 * @property string $status
 * @property DateTime|null $published_at
 * @property bool $comments_enabled
 * @property int $author_id
 * @property array<string, mixed> $data
 * @property DateTime $created
 * @property DateTime $modified
 * @property User|null $author
 * @property array<int, Tag> $tags
 */
final class Post extends Entity
{
    use HasFieldData;

    protected array $_accessible = [
        'title' => true,
        'slug' => true,
        'excerpt' => true,
        'body' => true,
        'status' => true,
        'published_at' => true,
        'comments_enabled' => true,
        'author_id' => true,
        'data' => true,
    ];

    public PostStatus $statusEnum {
        get => PostStatus::from($this->status);
    }
}
