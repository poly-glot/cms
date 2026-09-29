<?php

declare(strict_types=1);

namespace App\Service\Content;

enum ContentKind: string
{
    case Posts = 'posts';
    case Pages = 'pages';
    case Entries = 'entries';

    /**
     * @return list<self>
     */
    public static function fromOption(string $type): array
    {
        if ($type === 'all') {
            return self::cases();
        }

        $kind = self::tryFrom($type);

        return $kind === null ? [] : [$kind];
    }
}
