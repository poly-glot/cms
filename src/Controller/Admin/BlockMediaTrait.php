<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Block;
use App\Model\Entity\Media;

trait BlockMediaTrait
{
    private function blockMedia(Block $block): ?Media
    {
        return array_values($this->blockMediaMap([$block]))[0] ?? null;
    }

    /**
     * @param iterable<Block> $blocks
     * @return array<int, Media>
     */
    private function blockMediaMap(iterable $blocks): array
    {
        $mediaIds = [];
        foreach ($blocks as $block) {
            if (!$block->type->referencesMedia()) {
                continue;
            }
            $mediaId = $block->data['media_id'] ?? null;
            if (is_numeric($mediaId) && (int) $mediaId > 0) {
                $mediaIds[] = (int) $mediaId;
            }
        }
        if ($mediaIds === []) {
            return [];
        }

        $media = [];
        foreach ($this->fetchTable('Media')->find()->where(['id IN' => $mediaIds]) as $item) {
            $media[$item->id] = $item;
        }

        return $media;
    }
}
