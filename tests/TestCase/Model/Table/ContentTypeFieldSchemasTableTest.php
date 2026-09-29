<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\ContentTypeFieldSchema;
use App\Model\Enum\CollectionFieldType;
use App\Model\Enum\ContentType;
use App\Model\Enum\UserRole;
use App\Model\Table\ContentTypeFieldSchemasTable;
use App\Model\Tenancy\TenantContext;
use Cake\TestSuite\TestCase;

final class ContentTypeFieldSchemasTableTest extends TestCase
{
    protected array $fixtures = ['app.ContentTypeFieldSchemas'];
    private ContentTypeFieldSchemasTable $ContentTypeFieldSchemas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ContentTypeFieldSchemas = $this->fetchTable('ContentTypeFieldSchemas');
    }

    public function testSchemaForReturnsNewEmptyEntityWhenNoneStored(): void
    {
        $schema = $this->ContentTypeFieldSchemas->schemaFor(ContentType::Pages);

        $this->assertTrue($schema->isNew());
        $this->assertSame('Pages', $schema->subject_type);
        $this->assertSame([], $schema->fields);
    }

    public function testSchemaForReturnsStoredRow(): void
    {
        $stored = $this->ContentTypeFieldSchemas->newEmptyEntity();
        $stored->subject_type = 'Posts';
        $stored->field_schema = [['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text']];
        $this->ContentTypeFieldSchemas->saveOrFail($stored);

        $found = $this->ContentTypeFieldSchemas->schemaFor(ContentType::Posts);

        $this->assertFalse($found->isNew());
        $this->assertSame('subtitle', $found->fields[0]['name']);
        $this->assertSame(CollectionFieldType::Text, $found->fields[0]['type']);
    }

    public function testRejectsReservedFieldName(): void
    {
        $entity = $this->ContentTypeFieldSchemas->newEntity([
            'subject_type' => 'Pages',
            'field_schema' => [['name' => 'title', 'label' => 'Title', 'type' => 'text']],
        ]);

        $this->assertArrayHasKey('field_schema', $entity->getErrors());
    }

    public function testRejectsReferenceFieldType(): void
    {
        $entity = $this->ContentTypeFieldSchemas->newEntity([
            'subject_type' => 'Pages',
            'field_schema' => [['name' => 'related', 'label' => 'Related', 'type' => 'reference', 'target' => 'products', 'cardinality' => 'one']],
        ]);

        $this->assertArrayHasKey('field_schema', $entity->getErrors());
    }

    public function testRejectsTagsFieldType(): void
    {
        $entity = $this->ContentTypeFieldSchemas->newEntity([
            'subject_type' => 'Posts',
            'field_schema' => [['name' => 'topics', 'label' => 'Topics', 'type' => 'tags']],
        ]);

        $this->assertArrayHasKey('field_schema', $entity->getErrors());
    }

    public function testRejectsUnknownSubjectType(): void
    {
        $entity = $this->ContentTypeFieldSchemas->newEntity([
            'subject_type' => 'Widgets',
            'field_schema' => [['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text']],
        ]);

        $this->assertArrayHasKey('field_schema', $entity->getErrors());
    }

    public function testAcceptsAllowedTypesIncludingRepeater(): void
    {
        $entity = $this->ContentTypeFieldSchemas->newEntity([
            'subject_type' => 'Pages',
            'field_schema' => [
                ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text'],
                ['name' => 'gallery', 'label' => 'Gallery', 'type' => 'repeater', 'fields' => [
                    ['name' => 'caption', 'label' => 'Caption', 'type' => 'text'],
                ]],
            ],
        ]);
        $this->assertSame([], $entity->getErrors());

        $entity->subject_type = 'Pages';

        $this->assertNotFalse($this->ContentTypeFieldSchemas->save($entity));
    }

    public function testEnforcesUniquePerWorkspaceAndSubject(): void
    {
        $first = $this->ContentTypeFieldSchemas->newEmptyEntity();
        $first->subject_type = 'Pages';
        $first->field_schema = [];
        $this->ContentTypeFieldSchemas->saveOrFail($first);

        $second = $this->ContentTypeFieldSchemas->newEmptyEntity();
        $second->subject_type = 'Pages';
        $second->field_schema = [];

        $this->assertFalse($this->ContentTypeFieldSchemas->save($second));
        $this->assertArrayHasKey('subject_type', $second->getErrors());
    }

    public function testSchemaLookupIsTenantScoped(): void
    {
        $stored = $this->ContentTypeFieldSchemas->newEmptyEntity();
        $stored->subject_type = 'Pages';
        $stored->field_schema = [['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text']];
        $this->ContentTypeFieldSchemas->saveOrFail($stored);

        $fromOtherWorkspace = TenantContext::instance()->runScoped(
            2,
            UserRole::Admin,
            fn (): ContentTypeFieldSchema => $this->ContentTypeFieldSchemas->schemaFor(ContentType::Pages),
            'atelier',
        );

        $this->assertTrue($fromOtherWorkspace->isNew());
        $this->assertSame([], $fromOtherWorkspace->fields);
    }
}
