<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\FieldSchema;

use App\Model\Entity\Collection;
use App\Service\FieldSchema\FieldDataValidator;
use PHPUnit\Framework\TestCase;

final class FieldDataValidatorTest extends TestCase
{
    private FieldDataValidator $validator;
    private Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FieldDataValidator();
        $this->collection = new Collection(['field_schema' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'count', 'label' => 'Count', 'type' => 'number'],
            ['name' => 'active', 'label' => 'Active', 'type' => 'boolean'],
            ['name' => 'photo', 'label' => 'Photo', 'type' => 'media'],
            ['name' => 'level', 'label' => 'Level', 'type' => 'select', 'options' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'high', 'label' => 'High'],
            ]],
        ]]);
    }

    public function testCoercesValuesByType(): void
    {
        $result = $this->validator->validate($this->collection->fields, [
            'name' => 'Ada', 'count' => '42', 'active' => '1', 'photo' => '7', 'level' => 'low',
        ]);

        $this->assertSame('Ada', $result['data']['name']);
        $this->assertSame(42.0, $result['data']['count']);
        $this->assertTrue($result['data']['active']);
        $this->assertSame(7, $result['data']['photo']);
        $this->assertSame([], $result['errors']);
    }

    public function testFlagsRequiredFieldLeftEmpty(): void
    {
        $result = $this->validator->validate($this->collection->fields, ['name' => '', 'level' => 'low']);

        $this->assertArrayHasKey('name', $result['errors']);
    }

    public function testRejectsChoiceValueNotInOptions(): void
    {
        $result = $this->validator->validate($this->collection->fields, ['name' => 'A', 'level' => 'bogus']);

        $this->assertArrayHasKey('level', $result['errors']);
    }

    public function testAcceptsChoiceValueInOptions(): void
    {
        $result = $this->validator->validate($this->collection->fields, ['name' => 'A', 'level' => 'high']);

        $this->assertSame([], $result['errors']);
    }

    public function testDropsValuesForUnknownKeys(): void
    {
        $result = $this->validator->validate($this->collection->fields, ['name' => 'A', 'sneaky' => 'x']);

        $this->assertArrayNotHasKey('sneaky', $result['data']);
    }

    public function testCoercesRepeaterRows(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'skills', 'label' => 'Skills', 'type' => 'repeater', 'fields' => [
                ['name' => 'label', 'label' => 'Skill', 'type' => 'text', 'required' => true],
                ['name' => 'level', 'label' => 'Level', 'type' => 'number'],
            ]],
        ]]);

        $result = $this->validator->validate($collection->fields, ['skills' => [
            ['label' => 'PHP', 'level' => '5'],
            ['label' => 'JS', 'level' => '4'],
        ]]);

        $skills = $result['data']['skills'];
        $this->assertIsArray($skills);
        $this->assertCount(2, $skills);
        $first = $skills[0];
        $this->assertIsArray($first);
        $this->assertSame('PHP', $first['label']);
        $this->assertSame(5.0, $first['level']);
        $this->assertSame([], $result['errors']);
    }

    public function testFlagsRequiredRepeaterWithNoRows(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'skills', 'type' => 'repeater', 'required' => true, 'fields' => [
                ['name' => 'label', 'type' => 'text'],
            ]],
        ]]);

        $result = $this->validator->validate($collection->fields, ['skills' => []]);

        $this->assertArrayHasKey('skills', $result['errors']);
    }

    public function testCoercesSingleReferenceToInt(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'manager', 'type' => 'reference', 'target' => 'team', 'cardinality' => 'one'],
        ]]);

        $result = $this->validator->validate($collection->fields, ['manager' => '17']);

        $this->assertSame(17, $result['data']['manager']);
    }

    public function testCoercesManyReferenceToIntArrayDroppingNonNumeric(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'projects', 'type' => 'reference', 'target' => 'project', 'cardinality' => 'many'],
        ]]);

        $result = $this->validator->validate($collection->fields, ['projects' => ['7', '19', 'x']]);

        $this->assertSame([7, 19], $result['data']['projects']);
    }

    public function testCoercesTagsToKebabSlugsDedup(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'topics', 'type' => 'tags'],
        ]]);

        $result = $this->validator->validate($collection->fields, ['topics' => ['Hello World', 'design', 'design', '']]);

        $this->assertSame(['hello-world', 'design'], $result['data']['topics']);
    }

    public function testCoercesTagsToTheSameSlugsTagsAreSavedWith(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'topics', 'type' => 'tags'],
        ]]);

        $result = $this->validator->validate($collection->fields, ['topics' => ['Café Menu', 'a_b']]);

        $this->assertSame(['cafe-menu', 'a-b'], $result['data']['topics']);
    }

    public function testFlagsRequiredReferencesAndTagsLeftEmpty(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'manager', 'type' => 'reference', 'target' => 'team', 'cardinality' => 'one', 'required' => true],
            ['name' => 'projects', 'type' => 'reference', 'target' => 'project', 'cardinality' => 'many', 'required' => true],
            ['name' => 'topics', 'type' => 'tags', 'required' => true],
        ]]);

        $result = $this->validator->validate($collection->fields, ['manager' => 'x', 'projects' => ['x'], 'topics' => ['  ']]);

        $this->assertSame([
            'manager' => 'This field is required.',
            'projects' => 'This field is required.',
            'topics' => 'This field is required.',
        ], $result['errors']);
    }

    public function testFlagsRequiredSubFieldInRepeaterRowWithDottedPath(): void
    {
        $collection = new Collection(['field_schema' => [
            ['name' => 'skills', 'type' => 'repeater', 'fields' => [
                ['name' => 'label', 'type' => 'text', 'required' => true],
            ]],
        ]]);

        $result = $this->validator->validate($collection->fields, ['skills' => [['label' => '']]]);

        $this->assertArrayHasKey('skills.0.label', $result['errors']);
    }
}
