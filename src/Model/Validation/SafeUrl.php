<?php

declare(strict_types=1);

namespace App\Model\Validation;

final class SafeUrl
{
    public static function check(string $url): bool
    {
        if (str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, \PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }
}
