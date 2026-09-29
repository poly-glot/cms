<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use App\Model\Entity\Page;
use App\Model\Enum\ContentType;
use App\Model\Table\ContentTypeFieldSchemasTable;
use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use GraphQL\Deferred;

final class BatchLoader
{
    use LocatorAwareTrait;

    /** @var array<string, array<int, int>> */
    private array $queued = [];

    /** @var array<string, array<array-key, mixed>> */
    private array $loaded = [];

    /** @var array<int, string>|null */
    private ?array $pagePaths = null;

    /** @var array<string, list<array<string, mixed>>> */
    private array $contentSchemas = [];

    public function __construct(private readonly bool $canPreview)
    {
    }

    public function loadAuthor(?int $id): Deferred
    {
        return $this->defer('authors', $id, null, fn (array $ids): array => $this->fetchTable('Users')->find()
            ->where(['Users.id IN' => $ids])
            ->all()
            ->indexBy('id')
            ->toArray());
    }

    public function loadTags(string $type, int $id): Deferred
    {
        return $this->defer('tags.' . $type, $id, [], fn (array $ids): array => $this->fetchTable('Taggables')->find()
            ->where(['Taggables.taggable_type' => $type, 'Taggables.taggable_id IN' => $ids])
            ->contain(['Tags' => ['joinType' => 'INNER']])
            ->all()
            ->combine('tag_id', 'tag', 'taggable_id')
            ->toArray());
    }

    public function loadRenditions(int $mediaId): Deferred
    {
        return $this->defer('renditions', $mediaId, [], fn (array $ids): array => $this->fetchTable('MediaRenditions')->find()
            ->where(['MediaRenditions.media_id IN' => $ids])
            ->all()
            ->groupBy('media_id')
            ->toArray());
    }

    public function loadParentPage(?int $id): Deferred
    {
        return $this->defer('parents', $id, null, fn (array $ids): array => $this->visiblePages()
            ->where(['Pages.id IN' => $ids])
            ->all()
            ->indexBy('id')
            ->toArray());
    }

    public function loadChildPages(int $parentId): Deferred
    {
        return $this->defer('children', $parentId, [], fn (array $ids): array => $this->visiblePages()
            ->where(['Pages.parent_id IN' => $ids])
            ->orderByAsc('Pages.position')
            ->all()
            ->groupBy('parent_id')
            ->toArray());
    }

    public function loadReferences(int $sourceEntryId): Deferred
    {
        return $this->defer('references', $sourceEntryId, [], fn (array $ids): array => $this->fetchTable('CollectionEntryReferences')->find()
            ->where(['CollectionEntryReferences.source_entry_id IN' => $ids])
            ->contain(['TargetEntries' => fn (SelectQuery $query): SelectQuery => $this->canPreview ? $query : $query->find('live')])
            ->orderByAsc('CollectionEntryReferences.position')
            ->all()
            ->groupBy('source_entry_id')
            ->map(static fn (array $references): array => new Collection($references)
                ->combine('target_entry_id', 'target_entry', 'field_name')
                ->map(static fn (array $entries, int|string $fieldName): array => ['fieldName' => (string) $fieldName, 'entries' => array_values($entries)])
                ->toList())
            ->toArray());
    }

    public function pagePath(Page $page): string
    {
        $this->pagePaths ??= $this->fetchTable('Pages')->pathsById();
        $parentPath = $page->parent_id === null ? null : ($this->pagePaths[$page->parent_id] ?? null);

        return $parentPath === null ? $page->slug : $parentPath . '/' . $page->slug;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function contentFieldSchema(ContentType $type): array
    {
        if (isset($this->contentSchemas[$type->value])) {
            return $this->contentSchemas[$type->value];
        }

        /** @var ContentTypeFieldSchemasTable $table */
        $table = $this->fetchTable('ContentTypeFieldSchemas');

        return $this->contentSchemas[$type->value] = $table->schemaFor($type)->fields;
    }

    /**
     * @param callable(list<int>): array<array-key, mixed> $fetchByIds
     */
    private function defer(string $bucket, ?int $id, mixed $missing, callable $fetchByIds): Deferred
    {
        if ($id !== null) {
            $this->queued[$bucket][$id] = $id;
        }

        return new Deferred(function () use ($bucket, $id, $fetchByIds, $missing): mixed {
            $ids = array_values($this->queued[$bucket] ?? []);
            if ($ids !== []) {
                $this->queued[$bucket] = [];
                $this->loaded[$bucket] = $fetchByIds($ids) + ($this->loaded[$bucket] ?? []);
            }

            return $id === null ? $missing : ($this->loaded[$bucket][$id] ?? $missing);
        });
    }

    /**
     * @return SelectQuery<EntityInterface>
     */
    private function visiblePages(): SelectQuery
    {
        $query = $this->fetchTable('Pages')->find();

        return $this->canPreview ? $query : $query->find('live');
    }
}
