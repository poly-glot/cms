<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class CollectionEntriesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Collections', 'app.CollectionEntries', 'app.CollectionEntryReferences', 'app.Tags', 'app.Taggables'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexListsEntriesForCollection(): void
    {
        $this->get('/cabinet/admin/collections/1/entries');

        $this->assertResponseOk();
        $this->assertResponseContains('Maple Chair');
        $this->assertResponseContains('Walnut Stool');
    }

    public function testAddPersistsSchemaTypedData(): void
    {
        $this->post('/cabinet/admin/collections/1/entries/add', [
            'title' => 'Oak Bench',
            'slug' => 'oak-bench',
            'status' => 'draft',
            'data' => [
                'price' => '299.50',
                'description' => 'Sturdy oak bench.',
            ],
        ]);

        $entry = $this->fetchTable('CollectionEntries')
            ->find()->where(['slug' => 'oak-bench'])->firstOrFail();
        $this->assertRedirect('/cabinet/admin/collections/1/entries/edit/' . (int) $entry->id);
        $this->assertSame(299.5, $entry->data['price']);
        $this->assertSame('Sturdy oak bench.', $entry->data['description']);
        $this->assertSame(1, $entry->author_id);
    }

    public function testSlugMustBeUniqueWithinCollection(): void
    {
        $this->post('/cabinet/admin/collections/1/entries/add', [
            'title' => 'Duplicate', 'slug' => 'maple-chair', 'status' => 'draft',
            'data' => ['price' => '1'],
        ]);

        $this->assertResponseOk();
        $entries = $this->fetchTable('CollectionEntries')
            ->find()->where(['title' => 'Duplicate'])->count();
        $this->assertSame(0, $entries);
    }

    public function testEditPersistsChanges(): void
    {
        $this->post('/cabinet/admin/collections/1/entries/edit/1', [
            'title' => 'Maple Chair, refreshed',
            'slug' => 'maple-chair',
            'status' => 'live',
            'data' => ['price' => '450'],
        ]);

        $this->assertRedirect('/cabinet/admin/collections/1/entries/edit/1');
        $entry = $this->fetchTable('CollectionEntries')->get(1);
        $this->assertSame('Maple Chair, refreshed', $entry->title);
        $this->assertEquals(450, $entry->data['price']);
    }

    public function testAuthorCannotEditAnothersEntry(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'author']]);
        $this->post('/cabinet/admin/collections/1/entries/edit/1', [
            'title' => 'Hijacked', 'slug' => 'maple-chair', 'status' => 'draft', 'data' => [],
        ]);

        $this->assertResponseCode(403);
    }

    public function testAuthorCannotPublish(): void
    {
        $this->session(['Auth' => ['id' => 3, 'email' => 'jun@cabinet.local', 'name' => 'Jun', 'role' => 'author']]);
        $this->post('/cabinet/admin/collections/1/entries/edit/2', [
            'title' => 'Walnut Stool', 'slug' => 'walnut-stool',
            'status' => 'live', 'data' => ['price' => '180'],
        ]);

        $this->assertResponseCode(403);
    }

    public function testDeleteRemovesEntry(): void
    {
        $this->post('/cabinet/admin/collections/1/entries/delete/2');

        $this->assertRedirect('/cabinet/admin/collections/1/entries');
        $this->assertFalse($this->fetchTable('CollectionEntries')->exists(['id' => 2]));
    }

    public function testEntrySearchReturnsTargetCollectionEntries(): void
    {
        $this->get('/cabinet/admin/collections/entry-search?collection=products&q=Maple');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertResponseContains('Maple Chair');
        $this->assertResponseNotContains('Walnut Stool');
    }

    public function testEntryResolveReturnsTitlesForIds(): void
    {
        $this->get('/cabinet/admin/collections/entry-resolve?ids=1,2');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertResponseContains('Maple Chair');
        $this->assertResponseContains('Walnut Stool');
    }
}
