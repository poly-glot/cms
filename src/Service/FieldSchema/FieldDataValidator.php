<?php

declare(strict_types=1);

namespace App\Service\FieldSchema;

use App\Model\Entity\Tag;
use App\Model\Enum\CollectionFieldType;

final class FieldDataValidator
{
    /**
     * @param array<array-key, mixed> $fields
     * @param array<array-key, mixed> $raw
     * @return array{data: array<string, mixed>, errors: array<string, string>}
     */
    public function validate(array $fields, array $raw): array
    {
        $errors = [];
        $data = $this->coerceFields($fields, $raw, '', $errors);

        return ['data' => $data, 'errors' => $errors];
    }

    /**
     * @param array<array-key, mixed> $fields
     * @param array<array-key, mixed> $raw
     * @param array<string, string> $errors
     * @return array<string, mixed>
     */
    private function coerceFields(array $fields, array $raw, string $prefix, array &$errors): array
    {
        $data = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = is_string($field['name'] ?? null) ? $field['name'] : '';
            $type = $field['type'] ?? null;
            if ($name === '' || !$type instanceof CollectionFieldType) {
                continue;
            }

            $path = $prefix === '' ? $name : $prefix . '.' . $name;

            if ($type === CollectionFieldType::Repeater) {
                $subFields = is_array($field['fields'] ?? null) ? $field['fields'] : [];
                $data[$name] = $this->coerceRepeater($field, $subFields, $raw[$name] ?? null, $path, $errors);

                continue;
            }

            $isMany = ($field['cardinality'] ?? 'one') === 'many';
            $value = $this->coerce($raw[$name] ?? null, $type, $isMany);
            $data[$name] = $value;

            if (!empty($field['required']) && $type !== CollectionFieldType::Boolean && $this->isEmpty($value)) {
                $errors[$path] = 'This field is required.';

                continue;
            }

            $options = is_array($field['options'] ?? null) ? $field['options'] : [];
            if ($type === CollectionFieldType::Select && is_string($value) && $value !== '' && !$this->isAllowedOption($value, $options)) {
                $errors[$path] = 'Choose one of the listed options.';
            }
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $field
     * @param array<array-key, mixed> $subFields
     * @param array<string, string> $errors
     * @return list<array<string, mixed>>
     */
    private function coerceRepeater(array $field, array $subFields, mixed $rawRows, string $path, array &$errors): array
    {
        $rows = is_array($rawRows) ? array_values($rawRows) : [];

        $out = [];
        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $out[] = $this->coerceFields($subFields, $row, $path . '.' . $index, $errors);
            }
        }

        if (!empty($field['required']) && $out === []) {
            $errors[$path] = 'Add at least one row.';
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function tagSlugs(mixed $value): array
    {
        $candidates = is_array($value) ? $value : [$value];

        $slugs = [];
        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }
            $slug = Tag::kebabCase($candidate);
            if ($slug !== '' && !in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    private function singleId(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * @return list<int>
     */
    public static function idList(mixed $value): array
    {
        $candidates = is_array($value) ? $value : [$value];

        $ids = [];
        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                $ids[] = (int) $candidate;
            }
        }

        return $ids;
    }

    private function coerce(mixed $value, CollectionFieldType $type, bool $isMany): mixed
    {
        return match ($type) {
            CollectionFieldType::Number => is_numeric($value) ? (float) $value : null,
            CollectionFieldType::Boolean => !empty($value),
            CollectionFieldType::Media => is_numeric($value) ? (int) $value : null,
            CollectionFieldType::Reference => $isMany ? self::idList($value) : $this->singleId($value),
            CollectionFieldType::Tags => $this->tagSlugs($value),
            default => is_string($value) ? $value : null,
        };
    }

    private function isEmpty(mixed $value): bool
    {
        return in_array($value, [null, '', []], true);
    }

    /**
     * @param array<array-key, mixed> $options
     */
    private function isAllowedOption(string $value, array $options): bool
    {
        return array_any($options, static fn (mixed $option): bool => is_array($option) && ($option['value'] ?? null) === $value);
    }
}
