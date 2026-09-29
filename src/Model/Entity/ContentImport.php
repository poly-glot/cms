<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $subject_type
 * @property string $slug
 * @property string $hash
 * @property DateTime $imported_at
 */
final class ContentImport extends Entity
{
    protected array $_accessible = [
        'subject_type' => true,
        'slug' => true,
        'hash' => true,
        'imported_at' => true,
    ];
}
