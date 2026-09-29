<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Concern\HasFieldData;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $page_id
 * @property int $version
 * @property int $author_id
 * @property string $title
 * @property string $slug
 * @property string|null $body
 * @property string $status
 * @property string $template
 * @property DateTime|null $published_at
 * @property int|null $parent_id
 * @property string $visibility
 * @property string|null $note
 * @property array<string, mixed> $data
 * @property DateTime $created
 * @property Page|null $page
 * @property User|null $author
 */
final class PageRevision extends Entity
{
    use HasFieldData;

    protected array $_accessible = [
        'note' => true,
    ];
}
