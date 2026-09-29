<?php

declare(strict_types=1);

namespace App\Service\Page;

use App\Model\Entity\Page;
use App\Model\Enum\PostStatus;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use DomainException;

final class PagePublisher
{
    use LocatorAwareTrait;

    /** @param array<string, mixed> $data */
    public function saveDraft(Page $page, array $data, int $authorId): Page
    {
        $this->fetchTable('Pages')->patchEntity($page, $data + ['status' => PostStatus::Draft->value]);

        return $this->commit($page, $authorId);
    }

    /** @param array<string, mixed> $data */
    public function publish(Page $page, array $data, int $authorId): Page
    {
        $this->fetchTable('Pages')->patchEntity($page, $data + ['status' => PostStatus::Live->value]);
        $page->published_at = null;

        return $this->commit($page, $authorId);
    }

    /** @param array<string, mixed> $data */
    public function schedule(Page $page, array $data, DateTime $when, int $authorId): Page
    {
        if (!$when->greaterThan(DateTime::now())) {
            throw new DomainException('Scheduled pages need a future date.');
        }

        $this->fetchTable('Pages')->patchEntity($page, $data + [
            'status' => PostStatus::Scheduled->value,
            'published_at' => $when,
        ]);

        return $this->commit($page, $authorId);
    }

    public function publishScheduled(Page $page): Page
    {
        $page->status = PostStatus::Live->value;
        $page->published_at = null;

        return $this->commit($page, $page->author_id, 'Auto-published from schedule', ['checkRules' => false]);
    }

    public function restore(int $pageId, int $version, int $authorId): Page
    {
        $revision = $this->fetchTable('PageRevisions')->find()
            ->where(['page_id' => $pageId, 'version' => $version])
            ->first();
        if ($revision === null) {
            throw new NotFoundException(sprintf('Revision v%d for page %d not found.', $version, $pageId));
        }

        $page = $this->fetchTable('Pages')->find()->where(['id' => $pageId])->first();
        if ($page === null) {
            throw new NotFoundException(sprintf('Page %d not found.', $pageId));
        }

        $page->patch($revision->extract(PageRevisionWriter::SNAPSHOT_FIELDS), ['guard' => false]);

        return $this->commit($page, $authorId, sprintf('Restored from v%d', $version), ['checkRules' => false]);
    }

    /**
     * @param array<string, mixed> $saveOptions
     */
    private function commit(Page $page, int $authorId, ?string $note = null, array $saveOptions = []): Page
    {
        $pages = $this->fetchTable('Pages');
        $pages->getConnection()->transactional(static function () use ($pages, $page, $authorId, $note, $saveOptions): void {
            $pages->saveOrFail($page, $saveOptions);
            new PageRevisionWriter()->record($page, $authorId, $note);
        });

        return $page;
    }
}
