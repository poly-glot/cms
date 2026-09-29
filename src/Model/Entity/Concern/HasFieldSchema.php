<?php

declare(strict_types=1);

namespace App\Model\Entity\Concern;

use App\Model\Enum\CollectionFieldType;
use App\Model\FieldSchema\FieldSchema;

trait HasFieldSchema
{
    /**
     * @var list<array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>, fields?: list<array{name: string, label: string, type: CollectionFieldType, required: bool, options: list<array{value: string, label: string}>}>, target?: string, cardinality?: 'one'|'many'}>
     */
    public array $fields {
        get => FieldSchema::fromRaw($this->field_schema)->fields;
    }

    /**
     * @return list<array{name: string, label: string, type: string, required: bool, options?: list<array{value: string, label: string}>}>
     */
    protected function _getFieldSchema(mixed $value): array
    {
        return FieldSchema::normalise($value);
    }

    /**
     * @return list<array{name: string, label: string, type: string, required: bool, options?: list<array{value: string, label: string}>}>
     */
    protected function _setFieldSchema(mixed $value): array
    {
        return FieldSchema::normalise($value);
    }
}
