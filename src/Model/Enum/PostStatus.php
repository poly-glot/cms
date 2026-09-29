<?php

declare(strict_types=1);

namespace App\Model\Enum;

enum PostStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
    case Scheduled = 'scheduled';

    public function isPubliclyVisible(): bool
    {
        return $this === self::Live;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
