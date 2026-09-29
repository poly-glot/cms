<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PageRevisionsTable;
use Cake\TestSuite\TestCase;

final class PageRevisionsTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Pages', 'app.PageRevisions'];
    private PageRevisionsTable $PageRevisions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->PageRevisions = $this->fetchTable('PageRevisions');
    }

    public function testEnforcesUniqueVersionPerPage(): void
    {
        $revision = $this->PageRevisions->newEmptyEntity();
        $revision->page_id = 1;
        $revision->version = 1;
        $revision->author_id = 1;
        $revision->title = 'Duplicate';
        $revision->slug = 'about';
        $revision->body = '<p>dup</p>';
        $revision->status = 'draft';
        $revision->template = 'default';
        $revision->visibility = 'public';

        $saved = $this->PageRevisions->save($revision);

        $this->assertFalse($saved);
        $this->assertArrayHasKey('version', $revision->getErrors());
    }

    public function testListsRevisionsForPageDescByCreated(): void
    {
        $rows = $this->PageRevisions->find()
            ->where(['page_id' => 1])
            ->orderByDesc('created')
            ->toArray();

        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows[0]->version);
        $this->assertSame(1, $rows[1]->version);
    }

    public function testBelongsToPagesAndUsers(): void
    {
        $revision = $this->PageRevisions->get(1, contain: ['Pages', 'Authors']);

        $this->assertNotNull($revision->page);
        $this->assertSame(1, $revision->page->id);
        $this->assertNotNull($revision->author);
        $this->assertSame(1, $revision->author->id);
    }
}
