<?php

declare(strict_types=1);

namespace App\Service\Page;

use App\Model\Entity\Page;
use App\Model\Entity\PageRevision;
use Cake\ORM\Locator\LocatorAwareTrait;

final class PageRevisionWriter
{
    use LocatorAwareTrait;

    public const array SNAPSHOT_FIELDS = ['title', 'slug', 'body', 'data', 'status', 'template', 'published_at', 'parent_id', 'visibility'];

    public function record(Page $page, int $authorId, ?string $note = null): PageRevision
    {
        $revisions = $this->fetchTable('PageRevisions');
        $nextVersion = $this->nextVersion($page->id);

        $revision = $revisions->newEmptyEntity();
        $revision->page_id = $page->id;
        $revision->version = $nextVersion;
        $revision->author_id = $authorId;
        $revision->patch($page->extract(self::SNAPSHOT_FIELDS), ['guard' => false]);
        if ($note !== null) {
            $revision->note = $note;
        }

        $saved = $revisions->save($revision);
        if ($saved === false) {
            // unique constraint race; retry once with refreshed max
            $revision->version = $this->nextVersion($page->id);
            $saved = $revisions->saveOrFail($revision);
        }

        return $saved;
    }

    private function nextVersion(int $pageId): int
    {
        $revisions = $this->fetchTable('PageRevisions');
        $row = $revisions->find()
            ->where(['page_id' => $pageId])
            ->select(['v' => $revisions->find()->func()->max('version')])
            ->disableHydration()
            ->first();

        $max = is_array($row) && is_numeric($row['v']) ? (int) $row['v'] : 0;

        return $max + 1;
    }
}
