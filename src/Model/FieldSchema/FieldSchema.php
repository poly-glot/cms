<?php

declare(strict_types=1);

namespace App\Model\FieldSchema;

use App\Model\Enum\CollectionFieldType;

final readonly class FieldSchema
{
    /**
     * @param list<array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>, fields?: list<array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>}>, target?: string, cardinality?: 'one'|'many'}> $fields
     */
    private function __construct(public array $fields)
    {
    }

    public static function fromRaw(mixed $raw): self
    {
        return new self(self::typeFields(self::normalise($raw)));
    }

    /**
     * @return list<array{name: string, label: string, type: string, required: bool, options?: list<array{value: string, label: string}>}>
     */
    public static function normalise(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true);
        }

        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(self::normaliseField(...), $raw)));
    }

    /**
     * @param list<array{name: string, label: string, type: string, required: bool, options?: list<array{value: string, label: string}>, fields?: list<array<string, mixed>>, target?: string, cardinality?: 'one'|'many'}> $fields
     * @return list<array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>, fields?: list<array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>}>, target?: string, cardinality?: 'one'|'many'}>
     */
    private static function typeFields(array $fields): array
    {
        return array_values(array_filter(array_map(self::typeField(...), $fields)));
    }

    /**
     * @param array{name: string, label: string, type: string, required: bool, options?: list<array{value: string, label: string}>, fields?: list<array<string, mixed>>, target?: string, cardinality?: 'one'|'many'} $field
     * @return array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>, fields?: list<array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>}>, target?: string, cardinality?: 'one'|'many'}|null
     */
    private static function typeField(array $field): ?array
    {
        $type = CollectionFieldType::tryFrom($field['type']);
        if ($type === null) {
            return null;
        }

        $typed = [
            'name' => $field['name'],
            'label' => $field['label'],
            'type' => $type,
            'required' => $field['required'],
            'options' => $field['options'] ?? [],
        ];

        return match ($type) {
            CollectionFieldType::Reference => $typed + ['target' => $field['target'] ?? '', 'cardinality' => $field['cardinality'] ?? 'one'],
            CollectionFieldType::Repeater => $typed + ['fields' => self::typeFields(self::normalise($field['fields'] ?? []))],
            default => $typed,
        };
    }

    /**
     * @return array{name: string, label: string, type: string, required: bool, options?: list<array{value: string, label: string}>, fields?: list<array<string, mixed>>, target?: string, cardinality?: 'one'|'many'}|null
     */
    private static function normaliseField(mixed $row): ?array
    {
        if (!is_array($row)) {
            return null;
        }

        $name = is_string($row['name'] ?? null) ? trim($row['name']) : '';
        if ($name === '') {
            return null;
        }

        $rawLabel = $row['label'] ?? null;
        $field = [
            'name' => $name,
            'label' => is_string($rawLabel) && $rawLabel !== '' ? $rawLabel : $name,
            'type' => is_string($row['type'] ?? null) ? $row['type'] : 'text',
            'required' => !empty($row['required']),
        ];

        if (is_array($row['options'] ?? null)) {
            $field['options'] = self::normaliseOptions($row['options']);
        }

        if (($row['type'] ?? null) === 'repeater' && is_array($row['fields'] ?? null)) {
            $field['fields'] = self::normalise($row['fields']);
        }

        if (($row['type'] ?? null) === 'reference') {
            $field['target'] = is_string($row['target'] ?? null) ? $row['target'] : '';
            $field['cardinality'] = ($row['cardinality'] ?? null) === 'many' ? 'many' : 'one';
        }

        return $field;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private static function normaliseOptions(mixed $options): array
    {
        if (!is_array($options)) {
            return [];
        }

        $out = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            $value = is_string($option['value'] ?? null) ? $option['value'] : '';
            if ($value === '') {
                continue;
            }
            $rawLabel = $option['label'] ?? null;
            $out[] = [
                'value' => $value,
                'label' => is_string($rawLabel) && $rawLabel !== '' ? $rawLabel : $value,
            ];
        }

        return $out;
    }
}
