<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CollectionsTable;
use Cake\TestSuite\TestCase;

final class CollectionsTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Collections'];
    private CollectionsTable $Collections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Collections = $this->fetchTable('Collections');
    }

    public function testRequiresNameSlugAndFieldSchema(): void
    {
        $collection = $this->Collections->newEntity(['field_schema' => []]);

        $this->assertArrayHasKey('name', $collection->getErrors());
        $this->assertArrayHasKey('slug', $collection->getErrors());
    }

    public function testRejectsDuplicateFieldNames(): void
    {
        $collection = $this->Collections->newEntity([
            'name' => 'Test',
            'slug' => 'test',
            'field_schema' => [
                ['name' => 'a', 'label' => 'A', 'type' => 'text'],
                ['name' => 'a', 'label' => 'A2', 'type' => 'text'],
            ],
        ]);

        $this->assertArrayHasKey('field_schema', $collection->getErrors());
    }

    public function testRejectsUnknownFieldType(): void
    {
        $collection = $this->Collections->newEntity([
            'name' => 'Test',
            'slug' => 'test',
            'field_schema' => [
                ['name' => 'a', 'label' => 'A', 'type' => 'wizard'],
            ],
        ]);

        $this->assertArrayHasKey('field_schema', $collection->getErrors());
    }

    public function testEnforcesUniqueSlug(): void
    {
        $collection = $this->Collections->newEntity([
            'name' => 'Dup', 'slug' => 'products',
            'field_schema' => [['name' => 'x', 'label' => 'X', 'type' => 'text']],
        ]);

        $this->assertFalse($this->Collections->save($collection));
        $this->assertArrayHasKey('slug', $collection->getErrors());
    }

    public function testFieldsAccessorFiltersInvalidEntries(): void
    {
        $collection = $this->Collections->get(1);

        $fields = $collection->fields;

        $this->assertCount(2, $fields);
        $first = $fields[0];
        $this->assertSame('price', $first['name']);
        $this->assertTrue($first['required']);
    }
}
