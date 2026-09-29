<?php

declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Concern\HasFieldSchema;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $subject_type
 * @property list<array{name: string, label: string, type: string, required?: bool, options?: list<array{value: string, label: string}>}> $field_schema
 * @property DateTime $created
 * @property DateTime $modified
 */
final class ContentTypeFieldSchema extends Entity
{
    use HasFieldSchema;

    protected array $_accessible = [
        'field_schema' => true,
        'subject_type' => false,
    ];
}
