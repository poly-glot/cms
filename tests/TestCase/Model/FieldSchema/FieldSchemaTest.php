<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\FieldSchema;

use App\Model\Enum\CollectionFieldType;
use App\Model\FieldSchema\FieldSchema;
use Cake\TestSuite\TestCase;

final class FieldSchemaTest extends TestCase
{
    public function testNormaliseDecodesJsonStringAndFillsLabelFromName(): void
    {
        $normalised = FieldSchema::normalise('[{"name":"price","type":"number"}]');

        $this->assertCount(1, $normalised);
        $this->assertSame('price', $normalised[0]['name']);
        $this->assertSame('price', $normalised[0]['label']);
        $this->assertSame('number', $normalised[0]['type']);
        $this->assertFalse($normalised[0]['required']);
    }

    public function testNormaliseDropsRowsWithoutAName(): void
    {
        $normalised = FieldSchema::normalise([
            ['label' => 'No name', 'type' => 'text'],
            ['name' => 'kept', 'type' => 'text'],
        ]);

        $this->assertCount(1, $normalised);
        $this->assertSame('kept', $normalised[0]['name']);
    }

    public function testFromRawTypesFieldTypeAsEnum(): void
    {
        $schema = FieldSchema::fromRaw([
            ['name' => 'body', 'label' => 'Body', 'type' => 'rich_text', 'required' => true],
        ]);

        $this->assertSame(CollectionFieldType::RichText, $schema->fields[0]['type']);
        $this->assertTrue($schema->fields[0]['required']);
    }

    public function testFromRawDropsFieldWithUnknownType(): void
    {
        $schema = FieldSchema::fromRaw([
            ['name' => 'bad', 'type' => 'not_a_type'],
            ['name' => 'good', 'type' => 'text'],
        ]);

        $this->assertCount(1, $schema->fields);
        $this->assertSame('good', $schema->fields[0]['name']);
    }

    public function testFromRawTypesRepeaterSubFields(): void
    {
        $schema = FieldSchema::fromRaw([
            ['name' => 'gallery', 'label' => 'Gallery', 'type' => 'repeater', 'fields' => [
                ['name' => 'caption', 'label' => 'Caption', 'type' => 'text'],
            ]],
        ]);

        $repeater = $schema->fields[0];
        $subFields = $repeater['fields'] ?? [];

        $this->assertSame(CollectionFieldType::Repeater, $repeater['type']);
        $this->assertSame('caption', $subFields[0]['name']);
        $this->assertSame(CollectionFieldType::Text, $subFields[0]['type']);
    }

    public function testFromRawCoercesSelectOptionsAndDropsEmptyValues(): void
    {
        $schema = FieldSchema::fromRaw([
            ['name' => 'size', 'label' => 'Size', 'type' => 'select', 'options' => [
                ['value' => 's', 'label' => 'Small'],
                ['value' => '', 'label' => 'Dropped'],
                ['value' => 'l'],
            ]],
        ]);

        $options = $schema->fields[0]['options'];

        $this->assertCount(2, $options);
        $this->assertSame(['value' => 's', 'label' => 'Small'], $options[0]);
        $this->assertSame(['value' => 'l', 'label' => 'l'], $options[1]);
    }

    public function testFromRawTypesReferenceTargetAndCardinality(): void
    {
        $schema = FieldSchema::fromRaw([
            ['name' => 'related', 'label' => 'Related', 'type' => 'reference', 'target' => 'products', 'cardinality' => 'many'],
        ]);

        $field = $schema->fields[0];

        $this->assertSame(CollectionFieldType::Reference, $field['type']);
        $this->assertSame('products', $field['target'] ?? null);
        $this->assertSame('many', $field['cardinality'] ?? null);
    }
}
