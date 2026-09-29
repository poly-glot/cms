<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property int|null $parent_id
 * @property string $author_name
 * @property string|null $author_email
 * @property string $body
 * @property string $status
 * @property bool $is_read
 * @property DateTime $created
 * @property DateTime $modified
 * @property Page|null $page
 * @property Post|null $post
 * @property Comment|null $parent
 * @property array<Comment> $children
 */
final class Comment extends Entity
{
    protected array $_accessible = [
        'commentable_type' => true,
        'commentable_id' => true,
        'parent_id' => true,
        'author_name' => true,
        'author_email' => true,
        'body' => true,
    ];

    public string $avatarStyle {
        get => User::avatarStyleFor((string) $this->author_name);
    }
}
