<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $source_entry_id
 * @property string $field_name
 * @property int $target_entry_id
 * @property int $position
 * @property DateTime $created
 */
final class CollectionEntryReference extends Entity
{
    protected array $_accessible = [
        'source_entry_id' => true,
        'field_name' => true,
        'target_entry_id' => true,
        'position' => true,
    ];
}
