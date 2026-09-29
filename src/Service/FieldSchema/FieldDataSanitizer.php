<?php

declare(strict_types=1);

namespace App\Service\FieldSchema;

use App\Model\Enum\CollectionFieldType;
use App\Service\Page\BodySanitizer;

final class FieldDataSanitizer
{
    /**
     * @param array<array-key, mixed> $fields
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function clean(array $fields, array $data): array
    {
        return array_replace($data, $this->cleanedValues($fields, $data, new BodySanitizer()));
    }

    /**
     * @param array<array-key, mixed> $fields
     * @param array<array-key, mixed> $data
     * @return array<string, mixed>
     */
    private function cleanedValues(array $fields, array $data, BodySanitizer $sanitizer): array
    {
        $cleaned = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = is_string($field['name'] ?? null) ? $field['name'] : '';
            $type = $field['type'] ?? null;
            if ($name === '' || !$type instanceof CollectionFieldType) {
                continue;
            }

            $value = $data[$name] ?? null;
            if ($type === CollectionFieldType::RichText && is_string($value)) {
                $cleaned[$name] = $sanitizer->clean($value);

                continue;
            }

            if ($type === CollectionFieldType::Repeater && is_array($value)) {
                $subFields = is_array($field['fields'] ?? null) ? $field['fields'] : [];
                $cleaned[$name] = array_map(
                    fn (mixed $row): mixed => is_array($row) ? array_replace($row, $this->cleanedValues($subFields, $row, $sanitizer)) : $row,
                    $value,
                );
            }
        }

        return $cleaned;
    }
}
