<?php

declare(strict_types=1);

namespace App\GraphQL\Type\Scalar;

use Cake\I18n\DateTime;
use DateTimeInterface;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\CustomScalarType;
use Throwable;

final class DateTimeScalar extends CustomScalarType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'DateTime',
            'description' => 'An ISO-8601 encoded date-time string.',
            'serialize' => static function (mixed $value): ?string {
                if ($value instanceof DateTimeInterface) {
                    return $value->format(DateTimeInterface::ATOM);
                }

                return null;
            },
            'parseValue' => static function (mixed $value): DateTime {
                if (!is_string($value)) {
                    throw new Error('DateTime must be an ISO-8601 string.');
                }

                return self::parse($value);
            },
            'parseLiteral' => static function (Node $valueNode, ?array $variables = null): DateTime {
                if (!$valueNode instanceof StringValueNode) {
                    throw new Error('DateTime must be provided as a string.');
                }

                return self::parse($valueNode->value);
            },
        ]);
    }

    private static function parse(string $value): DateTime
    {
        try {
            return new DateTime($value);
        } catch (Throwable $exception) {
            throw new Error('Invalid DateTime value.', previous: $exception);
        }
    }
}
