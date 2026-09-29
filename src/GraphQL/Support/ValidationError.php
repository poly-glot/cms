<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use GraphQL\Error\Error;

final class ValidationError extends Error
{
    /**
     * @param list<array{field: string, rule: string|null, message: string}> $fields
     */
    public function __construct(public readonly array $fields)
    {
        parent::__construct('The submitted data was invalid.');
    }

    /**
     * @param array<string, string> $dottedErrors
     */
    public static function fromDottedPaths(array $dottedErrors): self
    {
        $fields = [];
        foreach ($dottedErrors as $path => $message) {
            $fields[] = ['field' => (string) $path, 'rule' => null, 'message' => $message];
        }

        return new self($fields);
    }
}
