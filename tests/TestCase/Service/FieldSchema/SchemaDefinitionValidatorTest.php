<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\FieldSchema;

use App\Service\FieldSchema\SchemaDefinitionValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchemaDefinitionValidatorTest extends TestCase
{
    /**
     * @return array<string, array{list<array<string, mixed>>}>
     */
    public static function validSchemas(): array
    {
        return [
            'scalar fields' => [[
                ['name' => 'headline', 'label' => 'Headline', 'type' => 'text'],
                ['name' => 'body', 'label' => 'Body', 'type' => 'rich_text', 'required' => true],
            ]],
            'empty list' => [[]],
            'repeater with sub-fields' => [[
                ['name' => 'skills', 'type' => 'repeater', 'fields' => [
                    ['name' => 'label', 'type' => 'text'],
                    ['name' => 'level', 'type' => 'select', 'options' => [['value' => 'a', 'label' => 'A']]],
                ]],
            ]],
            'one tags field' => [[
                ['name' => 'topics', 'type' => 'tags'],
            ]],
            'select with valid options' => [[
                ['name' => 'priority', 'type' => 'select', 'options' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'high', 'label' => 'High'],
                ]],
            ]],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidSchemas(): array
    {
        return [
            'non-list' => ['nope'],
            'field that is not a map' => [['just-a-string']],
            'empty name' => [[['name' => '', 'type' => 'text']]],
            'name with a space' => [[['name' => 'First Name', 'type' => 'text']]],
            'name with an uppercase letter' => [[['name' => 'Heading', 'type' => 'text']]],
            'name starting with a digit' => [[['name' => '1st', 'type' => 'text']]],
            'reserved name title' => [[['name' => 'title', 'type' => 'text']]],
            'reserved name status' => [[['name' => 'status', 'type' => 'text']]],
            'duplicate names' => [[
                ['name' => 'a', 'type' => 'text'],
                ['name' => 'a', 'type' => 'number'],
            ]],
            'unknown type' => [[['name' => 'a', 'type' => 'wizard']]],
            'select with empty options' => [[['name' => 'a', 'type' => 'select', 'options' => []]]],
            'select without options' => [[['name' => 'a', 'type' => 'select']]],
            'select option with empty value' => [[
                ['name' => 'a', 'type' => 'select', 'options' => [['value' => '', 'label' => 'Blank']]],
            ]],
            'select with duplicate option values' => [[
                ['name' => 'a', 'type' => 'select', 'options' => [
                    ['value' => 'x', 'label' => 'One'],
                    ['value' => 'x', 'label' => 'Two'],
                ]],
            ]],
            'repeater without sub-fields' => [[['name' => 'skills', 'type' => 'repeater', 'fields' => []]]],
            'repeater nested in repeater' => [[
                ['name' => 'outer', 'type' => 'repeater', 'fields' => [
                    ['name' => 'inner', 'type' => 'repeater', 'fields' => [['name' => 'x', 'type' => 'text']]],
                ]],
            ]],
            'tags nested in repeater' => [[
                ['name' => 'outer', 'type' => 'repeater', 'fields' => [
                    ['name' => 'topics', 'type' => 'tags'],
                ]],
            ]],
            'reference nested in repeater' => [[
                ['name' => 'outer', 'type' => 'repeater', 'fields' => [
                    ['name' => 'link', 'type' => 'reference'],
                ]],
            ]],
            'more than one tags field' => [[
                ['name' => 'topics', 'type' => 'tags'],
                ['name' => 'themes', 'type' => 'tags'],
            ]],
        ];
    }

    /**
     * @param list<array<string, mixed>> $schema
     */
    #[DataProvider('validSchemas')]
    public function testAcceptsValidSchema(array $schema): void
    {
        $this->assertTrue(new SchemaDefinitionValidator()->validate($schema));
    }

    #[DataProvider('invalidSchemas')]
    public function testRejectsInvalidSchemaWithAMessage(mixed $schema): void
    {
        $this->assertIsString(new SchemaDefinitionValidator()->validate($schema));
    }
}
