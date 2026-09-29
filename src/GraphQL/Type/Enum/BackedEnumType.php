<?php

declare(strict_types=1);

namespace App\GraphQL\Type\Enum;

use BackedEnum;
use GraphQL\Type\Definition\EnumType;

final class BackedEnumType extends EnumType
{
    /**
     * @param non-empty-string $name
     * @param list<BackedEnum> $cases
     */
    public function __construct(string $name, array $cases)
    {
        $values = [];
        foreach ($cases as $case) {
            $values[strtoupper((string) $case->value)] = ['value' => $case->value];
        }

        parent::__construct(['name' => $name, 'values' => $values]);
    }
}
