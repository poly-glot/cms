<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $setting_key
 * @property string|null $value
 * @property DateTime $created
 * @property DateTime $modified
 */
final class Setting extends Entity
{
    protected array $_accessible = [
        'setting_key' => true,
        'value' => true,
    ];
}
