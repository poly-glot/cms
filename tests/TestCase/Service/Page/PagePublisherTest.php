<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Page;

use App\Service\Page\PagePublisher;
use App\Service\Page\PageRevisionWriter;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use DomainException;

final class PagePublisherTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Pages', 'app.PageRevisions', 'app.Tags', 'app.Taggables'];

    public function testSaveDraftSetsDraftAndWritesRevision(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $publisher = new PagePublisher();

        $saved = $publisher->saveDraft($page, ['title' => 'Updated About'], authorId: 1);

        $this->assertSame('draft', $saved->status);
        $this->assertSame('Updated About', $saved->title);

        $revisions = $this->fetchTable('PageRevisions');
        $last = $revisions->find()->where(['page_id' => 1])->orderByDesc('version')->firstOrFail();
        $this->assertSame('Updated About', $last->title);
        $this->assertSame('draft', $last->status);
    }

    public function testPublishSetsLiveAndClearsPublishedAt(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $page->status = 'scheduled';
        $page->published_at = DateTime::now()->addDays(7);
        $pages->saveOrFail($page);

        $publisher = new PagePublisher();
        $saved = $publisher->publish($page, [], authorId: 1);

        $this->assertSame('live', $saved->status);
        $this->assertNull($saved->published_at);
    }

    public function testScheduleRequiresFutureDate(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $publisher = new PagePublisher();

        $this->expectException(DomainException::class);
        $publisher->schedule($page, [], when: DateTime::parse('2020-01-01 00:00:00'), authorId: 1);
    }

    public function testScheduleSetsStatusAndPublishedAt(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $publisher = new PagePublisher();
        $when = DateTime::now()->addDays(2);

        $saved = $publisher->schedule($page, [], when: $when, authorId: 1);

        $this->assertSame('scheduled', $saved->status);
        $this->assertNotNull($saved->published_at);
        $this->assertSame(
            $when->format('Y-m-d H:i'),
            $saved->published_at->format('Y-m-d H:i'),
        );
    }

    public function testPublishScheduledFlipsToLiveUsingPageAuthor(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $page->status = 'scheduled';
        $page->published_at = DateTime::now()->subMinutes(1);
        $pages->saveOrFail($page, ['checkRules' => false]);

        $publisher = new PagePublisher();
        $saved = $publisher->publishScheduled($page);

        $this->assertSame('live', $saved->status);

        $revisions = $this->fetchTable('PageRevisions');
        $last = $revisions->find()->where(['page_id' => 2])->orderByDesc('version')->firstOrFail();
        $this->assertSame('Auto-published from schedule', $last->note);
        $this->assertSame((int) $page->author_id, (int) $last->author_id);
    }

    public function testRestoresSnapshotIntoLivePage(): void
    {
        $pages = $this->fetchTable('Pages');
        $original = $pages->get(1);
        $original->title = 'Mutated title';
        $original->body = '<p>Mutated body</p>';
        $pages->saveOrFail($original, ['checkRules' => false]);

        $restored = new PagePublisher()->restore(pageId: 1, version: 1, authorId: 1);

        $this->assertSame('About Us', $restored->title);
        $this->assertSame('<p>Initial version.</p>', $restored->body);
    }

    public function testRestoreWritesNewRevisionWithRestoreNote(): void
    {
        new PagePublisher()->restore(pageId: 1, version: 1, authorId: 1);

        $revisions = $this->fetchTable('PageRevisions');
        $last = $revisions->find()->where(['page_id' => 1])->orderByDesc('version')->firstOrFail();

        $this->assertSame('Restored from v1', $last->note);
    }

    public function testRestoreThrowsWhenRevisionMissing(): void
    {
        $this->expectException(NotFoundException::class);
        new PagePublisher()->restore(pageId: 1, version: 999, authorId: 1);
    }

    public function testRestoresDataSnapshotIntoPage(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $page->data = ['subtitle' => 'Snapshot'];
        $pages->saveOrFail($page, ['checkRules' => false]);

        $snapshot = new PageRevisionWriter()->record($page, authorId: 1);

        $page->data = ['subtitle' => 'Changed'];
        $pages->saveOrFail($page, ['checkRules' => false]);

        $restored = new PagePublisher()->restore(pageId: 1, version: $snapshot->version, authorId: 1);

        $this->assertSame('Snapshot', $restored->data['subtitle']);
    }

    public function testRestoreSanitizesTheRevisionBody(): void
    {
        $pages = $this->fetchTable('Pages');
        $revisions = $this->fetchTable('PageRevisions');
        $snapshot = new PageRevisionWriter()->record($pages->get(1), authorId: 1);
        $revisions->updateAll(['body' => '<p>Kept</p><script>alert(1)</script>'], ['id' => $snapshot->id]);

        $restored = new PagePublisher()->restore(pageId: 1, version: $snapshot->version, authorId: 1);

        $this->assertStringContainsString('Kept', (string) $restored->body);
        $this->assertStringNotContainsString('<script', (string) $restored->body);
    }
}
