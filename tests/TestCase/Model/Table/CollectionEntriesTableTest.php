<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CollectionEntriesTable;
use Cake\TestSuite\TestCase;

final class CollectionEntriesTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Collections', 'app.CollectionEntries', 'app.CollectionEntryReferences', 'app.Tags', 'app.Taggables'];
    private CollectionEntriesTable $CollectionEntries;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CollectionEntries = $this->fetchTable('CollectionEntries');
    }

    private function addReferenceFieldToCollectionOne(string $cardinality): void
    {
        $collections = $this->fetchTable('Collections');
        $collection = $collections->get(1);
        $collections->patchEntity($collection, ['field_schema' => [
            ['name' => 'related', 'label' => 'Related', 'type' => 'reference', 'target' => 'products', 'cardinality' => $cardinality],
        ]]);
        $collections->saveOrFail($collection);
    }

    private function addTagsFieldToCollectionOne(): void
    {
        $collections = $this->fetchTable('Collections');
        $collection = $collections->get(1);
        $collections->patchEntity($collection, ['field_schema' => [
            ['name' => 'topics', 'label' => 'Topics', 'type' => 'tags'],
        ]]);
        $collections->saveOrFail($collection);
    }

    public function testTagSyncMaterializesTaggablesAndCreatesMissingTags(): void
    {
        $this->addTagsFieldToCollectionOne();

        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Tagged', 'slug' => 'tagged', 'status' => 'draft',
            'data' => ['topics' => ['heritage', 'brand-new']],
        ]);
        $entry->author_id = 1;
        $this->CollectionEntries->saveOrFail($entry);

        $taggables = $this->fetchTable('Taggables');
        $rows = $taggables->find()
            ->where(['taggable_type' => 'CollectionEntries', 'taggable_id' => $entry->id])
            ->all()->toList();
        $this->assertCount(2, $rows);

        $tags = $this->fetchTable('Tags');
        $this->assertSame(1, $tags->find()->where(['slug' => 'brand-new'])->count());
    }

    public function testTagSyncRemovesDroppedTags(): void
    {
        $this->addTagsFieldToCollectionOne();

        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Tagged', 'slug' => 'tagged', 'status' => 'draft',
            'data' => ['topics' => ['heritage', 'company']],
        ]);
        $entry->author_id = 1;
        $this->CollectionEntries->saveOrFail($entry);

        $this->CollectionEntries->patchEntity($entry, ['data' => ['topics' => ['heritage']]]);
        $this->CollectionEntries->saveOrFail($entry);

        $taggables = $this->fetchTable('Taggables');
        $count = $taggables->find()
            ->where(['taggable_type' => 'CollectionEntries', 'taggable_id' => $entry->id])
            ->count();
        $this->assertSame(1, $count);
    }

    public function testClearsTaggablesOnEntryDelete(): void
    {
        $this->addTagsFieldToCollectionOne();

        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Tagged', 'slug' => 'tagged', 'status' => 'draft',
            'data' => ['topics' => ['heritage']],
        ]);
        $entry->author_id = 1;
        $this->CollectionEntries->saveOrFail($entry);
        $entryId = $entry->id;

        $this->CollectionEntries->deleteOrFail($entry);

        $taggables = $this->fetchTable('Taggables');
        $this->assertSame(0, $taggables->find()
            ->where(['taggable_type' => 'CollectionEntries', 'taggable_id' => $entryId])
            ->count());
    }

    public function testAuthorIdIsNotMassAssignable(): void
    {
        $entry = $this->CollectionEntries->get(1);

        $this->CollectionEntries->patchEntity($entry, ['title' => 'Renamed', 'author_id' => 999]);

        $this->assertSame(1, $entry->author_id);
        $this->assertSame('Renamed', $entry->title);
    }

    public function testSlugAutoFillsFromTitleWhenBlank(): void
    {
        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Hand Carved Bowl', 'slug' => '', 'status' => 'draft', 'data' => [],
        ]);

        $this->assertSame('hand-carved-bowl', $entry->slug);
    }

    public function testKeepsAuthorSuppliedSlug(): void
    {
        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Hand Carved Bowl', 'slug' => 'my-custom', 'status' => 'draft', 'data' => [],
        ]);

        $this->assertSame('my-custom', $entry->slug);
    }

    public function testReconcilerWritesReferenceEdges(): void
    {
        $this->addReferenceFieldToCollectionOne('many');

        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Linker', 'slug' => 'linker', 'status' => 'draft',
            'data' => ['related' => [2]],
        ]);
        $entry->author_id = 1;
        $this->CollectionEntries->saveOrFail($entry);

        $references = $this->fetchTable('CollectionEntryReferences');
        $edges = $references->find()->where(['source_entry_id' => $entry->id])->all()->toList();

        $this->assertCount(1, $edges);
        $this->assertSame(2, $edges[0]->target_entry_id);
        $this->assertSame('related', $edges[0]->field_name);
    }

    public function testReconcilerDropsTargetIdNotInTargetCollection(): void
    {
        $this->addReferenceFieldToCollectionOne('many');

        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Linker', 'slug' => 'linker', 'status' => 'draft',
            'data' => ['related' => [99999]],
        ]);
        $entry->author_id = 1;
        $this->CollectionEntries->saveOrFail($entry);

        $references = $this->fetchTable('CollectionEntryReferences');
        $this->assertSame(0, $references->find()->where(['source_entry_id' => $entry->id])->count());
    }

    public function testCannotDeleteReferencedEntry(): void
    {
        $this->addReferenceFieldToCollectionOne('one');

        $source = $this->CollectionEntries->newEntity([
            'collection_id' => 1, 'title' => 'Linker', 'slug' => 'linker', 'status' => 'draft',
            'data' => ['related' => 2],
        ]);
        $source->author_id = 1;
        $this->CollectionEntries->saveOrFail($source);

        $target = $this->CollectionEntries->get(2);
        $deleted = $this->CollectionEntries->delete($target);

        $this->assertFalse($deleted);
        $this->assertTrue($this->CollectionEntries->exists(['id' => 2]));
    }

    public function testSanitizesRichTextDataOnSave(): void
    {
        $collections = $this->fetchTable('Collections');
        $collection = $collections->get(1);
        $collections->patchEntity($collection, [
            'field_schema_json' => null,
            'field_schema' => [['name' => 'note', 'label' => 'Note', 'type' => 'rich_text']],
        ]);
        $collections->saveOrFail($collection);

        $entry = $this->CollectionEntries->newEntity([
            'collection_id' => 1,
            'title' => 'With Note',
            'slug' => 'with-note',
            'status' => 'draft',
            'data' => ['note' => '<p>Safe</p><script>alert(1)</script>'],
        ]);
        $entry->author_id = 1;
        $this->CollectionEntries->saveOrFail($entry);

        $reloaded = $this->CollectionEntries->get($entry->id);
        $note = $reloaded->data['note'];
        $this->assertIsString($note);
        $this->assertStringNotContainsString('<script', $note);
        $this->assertStringContainsString('Safe', $note);
    }
}
