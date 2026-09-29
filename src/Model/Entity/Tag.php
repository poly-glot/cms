<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\Utility\Text;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $slug
 * @property string $label
 * @property DateTime $created
 */
final class Tag extends Entity
{
    protected array $_accessible = [
        'label' => true,
    ];

    protected function _setLabel(string $value): string
    {
        $trimmed = trim($value);
        if ($this->slug === null || $this->slug === '') {
            $this->slug = self::kebabCase($trimmed);
        }

        return $trimmed;
    }

    public static function kebabCase(string $value): string
    {
        return strtolower(Text::slug(trim($value), '-'));
    }
}
