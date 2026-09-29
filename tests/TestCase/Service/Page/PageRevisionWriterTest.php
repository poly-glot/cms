<?php

declare(strict_types=1);

namespace App\Test\TestCase\Service\Page;

use App\Service\Page\PageRevisionWriter;
use Cake\TestSuite\TestCase;

final class PageRevisionWriterTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Pages', 'app.PageRevisions'];

    public function testWritesIncrementingVersionPerPage(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $writer = new PageRevisionWriter();

        $revision = $writer->record($page, authorId: 1, note: 'manual');

        $this->assertSame(3, $revision->version);
        $this->assertSame(1, $revision->page_id);
        $this->assertSame('manual', $revision->note);
        $this->assertSame($page->title, $revision->title);
        $this->assertSame($page->slug, $revision->slug);
        $this->assertSame($page->body, $revision->body);
    }

    public function testStartsAtVersion1ForFreshPage(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2); // Draft Page has no revisions in fixture
        $writer = new PageRevisionWriter();

        $revision = $writer->record($page, authorId: 1, note: null);

        $this->assertSame(1, $revision->version);
        $this->assertNull($revision->note);
    }

    public function testSnapshotsAllFields(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(3); // Press, child of About
        $writer = new PageRevisionWriter();

        $revision = $writer->record($page, authorId: 1);

        $this->assertSame('press', $revision->slug);
        $this->assertSame(1, $revision->parent_id);
        $this->assertSame('default', $revision->template);
        $this->assertSame('public', $revision->visibility);
    }

    public function testCapturesPageDataInRevision(): void
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $page->data = ['subtitle' => 'Captured'];
        $pages->saveOrFail($page, ['checkRules' => false]);

        $revision = new PageRevisionWriter()->record($page, authorId: 1);

        $this->assertSame('Captured', $revision->data['subtitle']);
    }
}
