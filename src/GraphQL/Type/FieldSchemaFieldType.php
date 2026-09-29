<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\Model\Enum\CollectionFieldType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class FieldSchemaFieldType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'FieldSchemaField',
            'description' => 'A custom-field definition describing an entry data key.',
            'fields' => static fn (): array => [
                'name' => Type::nonNull(Type::string()),
                'label' => Type::nonNull(Type::string()),
                'type' => [
                    'type' => Type::nonNull(Registry::collectionFieldType()),
                    'resolve' => self::typeValue(...),
                ],
                'required' => Type::nonNull(Type::boolean()),
                'options' => Type::nonNull(Type::listOf(Type::nonNull(Registry::fieldOption()))),
                'target' => Type::string(),
                'cardinality' => Type::string(),
                'fields' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::fieldSchemaField()))),
                    'resolve' => self::subFields(...),
                ],
            ],
        ]);
    }

    /**
     * @param array{type: CollectionFieldType} $field
     */
    private static function typeValue(array $field): string
    {
        return $field['type']->value;
    }

    /**
     * @param array{fields?: list<array<string, mixed>>} $field
     * @return list<array<string, mixed>>
     */
    private static function subFields(array $field): array
    {
        return $field['fields'] ?? [];
    }
}
