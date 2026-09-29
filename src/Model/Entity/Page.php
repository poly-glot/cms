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
 * @property string|null $body
 * @property string $status
 * @property DateTime|null $published_at
 * @property string $template
 * @property string $visibility
 * @property bool $comments_enabled
 * @property int|null $parent_id
 * @property int $position
 * @property int $author_id
 * @property array<string, mixed> $data
 * @property DateTime $created
 * @property DateTime $modified
 * @property User|null $author
 * @property Page|null $parent_page
 * @property array<int, Page>|null $children
 * @property array<int, Tag> $tags
 * @property array<int, PageRevision> $page_revisions
 */
final class Page extends Entity
{
    use HasFieldData;

    protected array $_accessible = [
        'title' => true,
        'slug' => true,
        'body' => true,
        'status' => true,
        'published_at' => true,
        'template' => true,
        'visibility' => true,
        'comments_enabled' => true,
        'parent_id' => true,
        'position' => true,
        'author_id' => true,
        'data' => true,
        'tags' => true,
        'author' => false,
        'parent_page' => false,
        'page_revisions' => false,
    ];

    public PostStatus $statusEnum {
        get => PostStatus::from((string) $this->status);
    }
}
