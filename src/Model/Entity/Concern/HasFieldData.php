<?php

declare(strict_types=1);

namespace App\Model\Entity\Concern;

trait HasFieldData
{
    /** @return array<string, mixed> */
    protected function _getData(mixed $value): array
    {
        return self::normaliseFieldData($value);
    }

    /** @return array<string, mixed> */
    protected function _setData(mixed $value): array
    {
        return self::normaliseFieldData($value);
    }

    /**
     * @return array<string, mixed>
     */
    private static function normaliseFieldData(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            $value = json_decode($value, true);
        }
        if (!is_array($value)) {
            return [];
        }

        return array_filter($value, is_string(...), \ARRAY_FILTER_USE_KEY);
    }
}
