<?php

declare(strict_types=1);

namespace App\Model\Enum;

enum BlockType: string
{
    case Callout = 'callout';
    case Quote = 'quote';
    case Statistic = 'statistic';
    case Cta = 'cta';
    case Image = 'image';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Callout => 'Callout',
            self::Quote => 'Quote',
            self::Statistic => 'Statistic',
            self::Cta => 'Call to action',
            self::Image => 'Image',
            self::File => 'File',
        };
    }

    public function chipLabel(): string
    {
        return match ($this) {
            self::Cta => 'Call to Action',
            self::File => 'File Link',
            default => $this->label(),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Callout => 'A highlighted note — a tip, warning, or aside.',
            self::Quote => 'A pull quote with an optional attribution.',
            self::Statistic => 'A big number with a label and caption.',
            self::Cta => 'A heading and button that links somewhere.',
            self::Image => 'A picture with an optional caption.',
            self::File => 'A downloadable file with a label.',
        };
    }

    /**
     * @return list<string>
     */
    public function requiredKeys(): array
    {
        return match ($this) {
            self::Callout => ['variant', 'body'],
            self::Quote => ['text'],
            self::Statistic => ['value', 'label'],
            self::Cta => ['label', 'url', 'style'],
            self::Image, self::File => ['media_id'],
        };
    }

    public function referencesMedia(): bool
    {
        return match ($this) {
            self::Image, self::File => true,
            default => false,
        };
    }
}
