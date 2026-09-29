<?php

declare(strict_types=1);

namespace App\Model\Enum;

enum CollectionFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case RichText = 'rich_text';
    case Number = 'number';
    case Date = 'date';
    case DateTime = 'datetime';
    case Boolean = 'boolean';
    case Select = 'select';
    case Media = 'media';
    case Tags = 'tags';
    case Reference = 'reference';
    case Repeater = 'repeater';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Short text',
            self::Textarea => 'Long text',
            self::RichText => 'Rich text',
            self::Number => 'Number',
            self::Date => 'Date',
            self::DateTime => 'Date & time',
            self::Boolean => 'Yes / no',
            self::Select => 'Choice list',
            self::Media => 'Media',
            self::Tags => 'Tags',
            self::Reference => 'Linked entry',
            self::Repeater => 'Repeatable group',
        };
    }

    public function hasOptions(): bool
    {
        return $this === self::Select;
    }

    public function isStructured(): bool
    {
        return match ($this) {
            self::Reference, self::Repeater, self::Tags => true,
            default => false,
        };
    }
}
