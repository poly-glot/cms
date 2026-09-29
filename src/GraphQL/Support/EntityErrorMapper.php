<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use Cake\Log\Log;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\Utility\Hash;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;

/**
 * @phpstan-import-type SerializableError from ExecutionResult
 * @phpstan-import-type ErrorFormatter from ExecutionResult
 */
final class EntityErrorMapper
{
    /**
     * @param list<Error> $errors
     * @phpstan-param ErrorFormatter $formatter
     * @phpstan-return list<SerializableError>
     */
    public static function handle(array $errors, callable $formatter): array
    {
        $formatted = [];
        foreach ($errors as $error) {
            foreach (self::expand($error, $formatter) as $entry) {
                $formatted[] = $entry;
            }
        }

        return $formatted;
    }

    /**
     * @phpstan-param ErrorFormatter $formatter
     * @phpstan-return list<SerializableError>
     */
    private static function expand(Error $error, callable $formatter): array
    {
        $fieldErrors = self::fieldErrors($error);
        if ($fieldErrors !== []) {
            $base = $formatter($error);

            return array_map(
                static fn (array $fieldError): array => self::asValidation($base, $fieldError),
                $fieldErrors,
            );
        }

        $previous = $error->getPrevious();
        if ($previous !== null && !$error->isClientSafe()) {
            Log::error('GraphQL execution error', ['exception' => $previous]);
        }

        return [$formatter($error)];
    }

    /**
     * @return list<array{field: string, rule: string|null, message: string}>
     */
    private static function fieldErrors(Error $error): array
    {
        $previous = $error->getPrevious();

        if ($previous instanceof ValidationError) {
            return $previous->fields;
        }

        if ($previous instanceof PersistenceFailedException) {
            return self::flattenEntityErrors($previous->getEntity()->getErrors());
        }

        return [];
    }

    /**
     * @param array{field: string, rule: string|null, message: string} $fieldError
     * @phpstan-param SerializableError $base
     * @phpstan-return SerializableError
     */
    private static function asValidation(array $base, array $fieldError): array
    {
        $base['message'] = $fieldError['message'];
        $base['extensions'] = [
            'code' => 'VALIDATION',
            'field' => $fieldError['field'],
            'rule' => $fieldError['rule'],
        ];

        return $base;
    }

    /**
     * @param array<array-key, mixed> $errors
     * @return list<array{field: string, rule: string|null, message: string}>
     */
    private static function flattenEntityErrors(array $errors): array
    {
        $fieldErrors = [];
        foreach (array_filter(Hash::flatten($errors), is_string(...)) as $path => $message) {
            $separator = (int) strrpos((string) $path, '.');
            $rule = substr((string) $path, $separator + 1);

            $fieldErrors[] = [
                'field' => substr((string) $path, 0, $separator),
                'rule' => ctype_digit($rule) ? null : $rule,
                'message' => $message,
            ];
        }

        return $fieldErrors;
    }
}
