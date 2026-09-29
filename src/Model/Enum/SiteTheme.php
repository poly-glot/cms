<?php

declare(strict_types=1);

namespace App\Model\Enum;

/**
 * The closed set of public themes. Each is a palette skin layered over the
 * shared heritage base stylesheet (the public templates are theme-agnostic and
 * driven entirely by CSS custom properties), so a theme adds at most one
 * override stylesheet on top of heritage.css.
 */
enum SiteTheme: string
{
    case Heritage = 'heritage';
    case AtelierDark = 'atelier-dark';
    case Paper = 'paper';

    public static function fromValue(string $value): self
    {
        return self::tryFrom($value) ?? self::Heritage;
    }

    public function label(): string
    {
        return match ($this) {
            self::Heritage => 'Heritage',
            self::AtelierDark => 'Atelier Dark',
            self::Paper => 'Paper',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Heritage => 'A warm, serif-led theme suited to long-form storytelling.',
            self::AtelierDark => 'An inky, gallery-mode counterpart for image-heavy sites.',
            self::Paper => 'A minimal print-inspired theme with generous margins.',
        };
    }

    /** Per-theme swatch colours for the admin preview tile (dimensions live in CSS). */
    public function swatchStyle(): string
    {
        return match ($this) {
            self::Heritage => 'background: linear-gradient(180deg, #f3eee2, #cdb98a); border: 1px solid #b8ad93; box-shadow: inset 0 1px 0 #fff;',
            self::AtelierDark => 'background: linear-gradient(180deg, #2a2d33, #0f1115); border: 1px solid #0a0c0f; box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);',
            self::Paper => 'background: linear-gradient(180deg, #fff, #e6e1d2); border: 1px solid #b8ad93; box-shadow: inset 0 1px 0 #fff;',
        };
    }

    /**
     * Public stylesheets to load, in order: the heritage base first, then this
     * theme's palette override (none for Heritage itself).
     *
     * @return list<string>
     */
    public function stylesheets(): array
    {
        return $this === self::Heritage ? ['heritage'] : ['heritage', $this->value];
    }
}
