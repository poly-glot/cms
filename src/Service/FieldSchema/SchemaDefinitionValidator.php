<?php

declare(strict_types=1);

namespace App\Service\FieldSchema;

use App\Model\Enum\CollectionFieldType;

final readonly class SchemaDefinitionValidator
{
    private const string NAME_PATTERN = '/^[a-z][a-z0-9_]{0,49}$/';

    private const array COLLECTION_RESERVED = [
        'id', 'collection_id', 'workspace_id', 'title', 'slug', 'status',
        'published_at', 'author_id', 'comments_enabled', 'data', 'created', 'modified',
    ];

    /**
     * @param list<string> $reserved
     * @param list<CollectionFieldType>|null $allowedTypes
     */
    public function __construct(
        private array $reserved = self::COLLECTION_RESERVED,
        private ?array $allowedTypes = null,
    ) {
    }

    public function validate(mixed $schema): bool|string
    {
        if (!is_array($schema)) {
            return 'Field schema must be a list.';
        }

        $seen = [];
        $tagsFields = 0;
        foreach ($schema as $field) {
            $result = $this->validateField($field, $seen, false);
            if ($result !== true) {
                return $result;
            }
            if (is_array($field) && ($field['type'] ?? null) === CollectionFieldType::Tags->value) {
                ++$tagsFields;
            }
        }

        if ($tagsFields > 1) {
            return 'A collection can have only one tags field.';
        }

        return true;
    }

    private function isAllowedType(CollectionFieldType $type): bool
    {
        return $this->allowedTypes === null || in_array($type, $this->allowedTypes, true);
    }

    /**
     * @param array<string, bool> $seen
     */
    private function validateField(mixed $field, array &$seen, bool $nested): bool|string
    {
        if (!is_array($field)) {
            return 'Each field must be a key/value map.';
        }

        $name = $field['name'] ?? null;
        if (!is_string($name) || preg_match(self::NAME_PATTERN, $name) !== 1) {
            return 'Field names must start with a letter and use only lowercase letters, digits, or underscores.';
        }

        if (!$nested && in_array($name, $this->reserved, true)) {
            return 'Reserved field name: ' . $name;
        }

        if (isset($seen[$name])) {
            return 'Duplicate field name: ' . $name;
        }
        $seen[$name] = true;

        $rawType = $field['type'] ?? null;
        $type = is_string($rawType) ? CollectionFieldType::tryFrom($rawType) : null;
        if ($type === null || !$this->isAllowedType($type)) {
            return 'Unknown field type: ' . (is_string($rawType) ? $rawType : '?');
        }

        if ($nested && $type->isStructured()) {
            return 'A repeatable group can only contain simple fields.';
        }

        if ($type === CollectionFieldType::Select) {
            return $this->validateOptions($field['options'] ?? null, $name);
        }

        if ($type === CollectionFieldType::Repeater) {
            return $this->validateRepeater($field['fields'] ?? null, $name);
        }

        if ($type === CollectionFieldType::Reference) {
            return $this->validateReference($field, $name);
        }

        return true;
    }

    /**
     * @param array<array-key, mixed> $field
     */
    private function validateReference(array $field, string $name): bool|string
    {
        $target = $field['target'] ?? null;
        if (!is_string($target) || $target === '') {
            return 'Linked-entry field "' . $name . '" needs a target collection.';
        }

        $cardinality = $field['cardinality'] ?? 'one';
        if ($cardinality !== 'one' && $cardinality !== 'many') {
            return 'Linked-entry field "' . $name . '" has an invalid cardinality.';
        }

        return true;
    }

    private function validateRepeater(mixed $fields, string $parentName): bool|string
    {
        if (!is_array($fields) || $fields === []) {
            return 'Repeatable group "' . $parentName . '" needs at least one field.';
        }

        $seen = [];
        foreach ($fields as $field) {
            $result = $this->validateField($field, $seen, true);
            if ($result !== true) {
                return $result;
            }
        }

        return true;
    }

    private function validateOptions(mixed $options, string $fieldName): bool|string
    {
        if (!is_array($options) || $options === []) {
            return 'Choice list "' . $fieldName . '" needs at least one option.';
        }

        $values = [];
        foreach ($options as $option) {
            $value = is_array($option) ? ($option['value'] ?? null) : null;
            if (!is_string($value) || $value === '') {
                return 'Each option in "' . $fieldName . '" needs a value.';
            }
            if (isset($values[$value])) {
                return 'Duplicate option value in "' . $fieldName . '": ' . $value;
            }
            $values[$value] = true;
        }

        return true;
    }
}
