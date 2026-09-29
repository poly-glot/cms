<?php

declare(strict_types=1);

namespace App\Service\Collection;

use App\Model\Entity\Collection;
use App\Model\Entity\CollectionEntry;
use App\Model\Enum\CollectionFieldType;
use App\Model\Table\TagsTable;
use App\Model\Tenancy\TenantContext;
use Cake\ORM\Locator\LocatorAwareTrait;

final class TagSyncer
{
    use LocatorAwareTrait;

    private const string ENTRY_TAGGABLE_TYPE = 'CollectionEntries';

    public function sync(CollectionEntry $entry): void
    {
        $collection = $this->fetchTable('Collections')->find()
            ->where(['Collections.id' => $entry->collection_id])
            ->first();
        if (!$collection instanceof Collection) {
            return;
        }

        $tagField = $this->tagFieldName($collection);
        $slugs = $tagField === null ? [] : $entry->data[$tagField] ?? [];

        $this->replace(self::ENTRY_TAGGABLE_TYPE, $entry->id, is_array($slugs) ? $slugs : []);
    }

    /**
     * @param array<array-key, mixed> $slugs
     */
    public function replace(string $taggableType, int $taggableId, array $slugs): void
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $tagIds = $this->resolveTagIds($slugs);

        $taggables = $this->fetchTable('Taggables');
        $taggables->getConnection()->transactional(static function () use ($taggables, $taggableType, $taggableId, $workspaceId, $tagIds): void {
            $taggables->deleteAll([
                'taggable_type' => $taggableType,
                'taggable_id' => $taggableId,
                'workspace_id' => $workspaceId,
            ]);

            $rows = array_map(static fn (int $tagId): object => $taggables->newEntity([
                'taggable_type' => $taggableType,
                'taggable_id' => $taggableId,
                'tag_id' => $tagId,
            ]), $tagIds);

            if ($rows !== []) {
                $taggables->saveManyOrFail($rows);
            }
        });
    }

    public function clear(CollectionEntry $entry): void
    {
        $this->fetchTable('Taggables')->deleteAll([
            'taggable_type' => self::ENTRY_TAGGABLE_TYPE,
            'taggable_id' => $entry->id,
            'workspace_id' => TenantContext::instance()->requireWorkspaceId(),
        ]);
    }

    private function tagFieldName(Collection $collection): ?string
    {
        foreach ($collection->fields as $field) {
            if ($field['type'] === CollectionFieldType::Tags) {
                return $field['name'];
            }
        }

        return null;
    }

    /**
     * @param array<array-key, mixed> $slugs
     * @return list<int>
     */
    private function resolveTagIds(array $slugs): array
    {
        $tags = $this->fetchTable(TagsTable::class);

        $ids = [];
        foreach ($slugs as $slug) {
            $value = is_string($slug) ? trim($slug) : '';
            if ($value === '') {
                continue;
            }

            $tag = $tags->findOrCreateBySlug($value);
            $ids[$tag->id] = $tag->id;
        }

        return array_values($ids);
    }
}
