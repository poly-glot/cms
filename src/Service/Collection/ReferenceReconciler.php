<?php

declare(strict_types=1);

namespace App\Service\Collection;

use App\Model\Entity\Collection;
use App\Model\Entity\CollectionEntry;
use App\Model\Enum\CollectionFieldType;
use App\Model\Tenancy\TenantContext;
use App\Service\FieldSchema\FieldDataValidator;
use Cake\ORM\Locator\LocatorAwareTrait;

final class ReferenceReconciler
{
    use LocatorAwareTrait;

    public function reconcile(CollectionEntry $entry): void
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $references = $this->fetchTable('CollectionEntryReferences');

        $collection = $this->fetchTable('Collections')->find()
            ->where(['Collections.id' => $entry->collection_id])
            ->first();
        if (!$collection instanceof Collection) {
            return;
        }

        $edges = [];
        foreach ($collection->fields as $field) {
            if ($field['type'] !== CollectionFieldType::Reference) {
                continue;
            }

            $name = $field['name'];
            $ids = FieldDataValidator::idList($entry->data[$name] ?? null);
            $validIds = $this->validTargetIds($ids, $field['target'] ?? '');

            $position = 0;
            foreach ($validIds as $targetId) {
                $edge = $references->newEmptyEntity();
                $edge->source_entry_id = $entry->id;
                $edge->field_name = $name;
                $edge->target_entry_id = $targetId;
                $edge->position = $position;
                $edges[] = $edge;
                ++$position;
            }
        }

        $references->getConnection()->transactional(static function () use ($references, $entry, $workspaceId, $edges): void {
            $references->deleteAll(['source_entry_id' => $entry->id, 'workspace_id' => $workspaceId]);
            if ($edges !== []) {
                $references->saveManyOrFail($edges);
            }
        });
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function validTargetIds(array $ids, string $targetSlug): array
    {
        if ($ids === [] || $targetSlug === '') {
            return [];
        }

        $slugById = $this->fetchTable('CollectionEntries')
            ->find('list', keyField: 'id', valueField: 'slug')
            ->find('byCollectionSlug', collectionSlug: $targetSlug)
            ->where(['CollectionEntries.id IN' => $ids])
            ->toArray();

        return array_values(array_filter($ids, static fn (int $id): bool => isset($slugById[$id])));
    }
}
