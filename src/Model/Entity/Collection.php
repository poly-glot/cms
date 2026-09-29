<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Concern\HasFieldSchema;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property list<array{name: string, label: string, type: string, required?: bool, options?: list<array{value: string, label: string}>}> $field_schema
 * @property DateTime $created
 * @property DateTime $modified
 */
final class Collection extends Entity
{
    use HasFieldSchema;

    protected array $_accessible = [
        'name' => true,
        'slug' => true,
        'description' => true,
        'field_schema' => true,
    ];
}
