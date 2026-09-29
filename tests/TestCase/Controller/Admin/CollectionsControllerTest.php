<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class CollectionsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Collections', 'app.CollectionEntries'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexListsCollections(): void
    {
        $this->get('/cabinet/admin/collections');

        $this->assertResponseOk();
        $this->assertResponseContains('Products');
        $this->assertResponseContains('+ New collection');
    }

    public function testAddCreatesCollectionWithFieldSchema(): void
    {
        $this->post('/cabinet/admin/collections/add', [
            'name' => 'Recipes',
            'slug' => 'recipes',
            'field_schema_json' => (string) json_encode([
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true],
                ['name' => 'steps', 'label' => 'Steps', 'type' => 'textarea'],
            ]),
        ]);

        $created = $this->fetchTable('Collections')
            ->find()->where(['slug' => 'recipes'])->firstOrFail();
        $this->assertRedirect('/cabinet/admin/collections/edit/' . (int) $created->id);
        $schema = $created->field_schema;
        $this->assertCount(2, $schema);
        $this->assertSame('heading', $schema[0]['name'] ?? null);
        $this->assertTrue($schema[0]['required'] ?? false);
    }

    public function testAddIgnoresEmptyFieldRows(): void
    {
        $this->post('/cabinet/admin/collections/add', [
            'name' => 'Things',
            'slug' => 'things',
            'field_schema_json' => (string) json_encode([
                ['name' => 'a', 'type' => 'text'],
                ['name' => '', 'type' => 'text'],
            ]),
        ]);

        $created = $this->fetchTable('Collections')
            ->find()->where(['slug' => 'things'])->firstOrFail();
        $this->assertCount(1, $created->field_schema);
    }

    public function testAddCreatesChoiceFieldWithOptions(): void
    {
        $this->post('/cabinet/admin/collections/add', [
            'name' => 'Tickets',
            'slug' => 'tickets',
            'field_schema_json' => (string) json_encode([
                ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'high', 'label' => 'High'],
                ]],
            ]),
        ]);

        $created = $this->fetchTable('Collections')
            ->find()->where(['slug' => 'tickets'])->firstOrFail();
        $this->assertCount(2, $created->field_schema[0]['options'] ?? []);
    }

    public function testRejectsReservedFieldName(): void
    {
        $this->post('/cabinet/admin/collections/edit/1', [
            'name' => 'Products',
            'slug' => 'products',
            'field_schema_json' => (string) json_encode([
                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
            ]),
        ]);

        $unchanged = $this->fetchTable('Collections')->get(1);
        $this->assertSame('price', $unchanged->field_schema[0]['name'] ?? null);
    }

    public function testRejectsChoiceFieldWithoutOptions(): void
    {
        $this->post('/cabinet/admin/collections/edit/1', [
            'name' => 'Products',
            'slug' => 'products',
            'field_schema_json' => (string) json_encode([
                ['name' => 'kind', 'label' => 'Kind', 'type' => 'select', 'options' => []],
            ]),
        ]);

        $unchanged = $this->fetchTable('Collections')->get(1);
        $this->assertSame('price', $unchanged->field_schema[0]['name'] ?? null);
    }

    public function testListReturnsCollectionsAsJson(): void
    {
        $this->get('/cabinet/admin/collections/list');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertResponseContains('"slug":"products"');
    }

    public function testNonEditorialCannotCreate(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'contributor']]);
        $this->post('/cabinet/admin/collections/add', [
            'name' => 'Sneaky', 'slug' => 'sneaky',
            'field_schema_json' => (string) json_encode([['name' => 'x', 'type' => 'text']]),
        ]);

        $this->assertResponseCode(403);
    }

    public function testNonEditorialCannotEditSchema(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'contributor']]);
        $this->post('/cabinet/admin/collections/edit/1', [
            'name' => 'Products', 'slug' => 'products',
            'field_schema_json' => (string) json_encode([['name' => 'x', 'type' => 'text']]),
        ]);

        $this->assertResponseCode(403);
    }

    public function testNonEditorialCannotDelete(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'contributor']]);
        $this->post('/cabinet/admin/collections/delete/1');

        $this->assertResponseCode(403);
    }

    public function testSchemaBuilderAddsFieldRowViaJsonPayload(): void
    {
        $schema = [
            ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false],
            ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'required' => true],
        ];
        $this->post('/cabinet/admin/collections/edit/1', [
            'name' => 'Products',
            'slug' => 'products',
            'field_schema_json' => json_encode($schema),
        ]);

        $saved = $this->fetchTable('Collections')->get(1);
        $this->assertCount(3, $saved->field_schema);
        $this->assertSame('sku', $saved->field_schema[2]['name'] ?? null);
    }

    public function testDeleteRemovesCollectionAndEntries(): void
    {
        $this->post('/cabinet/admin/collections/delete/1');

        $this->assertRedirect('/cabinet/admin/collections');
        $this->assertFalse($this->fetchTable('Collections')->exists(['id' => 1]));
        $this->assertFalse($this->fetchTable('CollectionEntries')->exists(['collection_id' => 1]));
    }
}
