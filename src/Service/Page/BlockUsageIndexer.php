<?php

declare(strict_types=1);

namespace App\Service\Page;

use App\Model\Entity\Page;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Keeps the `page_blocks` usage index in sync with the block references found
 * in a page body. Runs as a full replace on every page save.
 */
final class BlockUsageIndexer
{
    use LocatorAwareTrait;

    public function reindex(Page $page): void
    {
        $pageBlocks = $this->fetchTable('PageBlocks');
        $pageId = $page->id;
        $referenced = $this->existingBlockIds(BlockReferenceScanner::ids((string) $page->body));

        $workspaceId = $page->workspace_id;

        $pageBlocks->getConnection()->transactional(static function () use ($pageBlocks, $pageId, $workspaceId, $referenced): void {
            $pageBlocks->deleteAll(['page_id' => $pageId, 'workspace_id' => $workspaceId]);
            foreach ($referenced as $blockId) {
                $link = $pageBlocks->newEmptyEntity();
                $link->page_id = $pageId;
                $link->block_id = $blockId;
                $pageBlocks->saveOrFail($link);
            }
        });
    }

    /**
     * Filter to ids that actually exist so a stale reference never violates the
     * page_blocks -> blocks foreign key.
     *
     * @param list<int> $ids
     * @return list<int>
     */
    private function existingBlockIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $existing = [];
        foreach ($this->fetchTable('Blocks')->find()->select(['id'])->where(['id IN' => $ids]) as $block) {
            $existing[] = $block->id;
        }

        return $existing;
    }
}
