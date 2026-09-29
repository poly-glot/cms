<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

final class PagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Pages', 'app.PageRevisions', 'app.Tags', 'app.Taggables', 'app.ContentTypeFieldSchemas'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testAnonymousRedirectsToLogin(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/pages');

        $this->assertRedirectContains('/login');
    }

    public function testIndexShowsExistingPages(): void
    {
        $this->get('/cabinet/admin/pages');

        $this->assertResponseOk();
        $this->assertResponseContains('About Us');
        $this->assertResponseContains('Draft Page');
    }

    public function testAddCreatesPage(): void
    {
        $this->post('/cabinet/admin/pages/add', [
            'title' => 'New Page',
            'slug' => 'new-page',
            'body' => '<p>Hello.</p>',
            'status' => 'draft',
        ]);

        $this->assertRedirectContains('/cabinet/admin/pages/edit/');
    }

    public function testAddRejectsDuplicateSlug(): void
    {
        $this->post('/cabinet/admin/pages/add', [
            'title' => 'Dup',
            'slug' => 'about',
            'body' => '',
            'status' => 'draft',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('already');
    }

    public function testEditUpdatesPage(): void
    {
        $this->put('/cabinet/admin/pages/edit/1', [
            'title' => 'About Us (edited)',
            'slug' => 'about',
            'body' => '<p>Updated.</p>',
            'status' => 'live',
        ]);

        $this->assertRedirect('/cabinet/admin/pages/edit/1');
        $this->assertFlashMessage('Page saved.');
    }

    public function testDeleteRemovesPage(): void
    {
        $this->post('/cabinet/admin/pages/delete/2');

        $this->assertRedirect('/cabinet/admin/pages');
    }

    public function testSaveDraftWritesDraftStatusAndRevision(): void
    {
        $this->post('/cabinet/admin/pages/save-draft/1', [
            'title' => 'About (working draft)',
            'slug' => 'about',
            'body' => '<p>Working draft.</p>',
        ]);

        $this->assertRedirect('/cabinet/admin/pages/edit/1');

        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $this->assertSame('draft', $page->status);

        $revisions = $this->fetchTable('PageRevisions');
        $last = $revisions->find()->where(['page_id' => 1])->orderByDesc('version')->firstOrFail();
        $this->assertSame('About (working draft)', $last->title);
    }

    public function testPublishSetsLive(): void
    {
        $this->post('/cabinet/admin/pages/publish/2', [
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'body' => '<p>Now live.</p>',
        ]);

        $this->assertRedirect('/cabinet/admin/pages/edit/2');

        $pages = $this->fetchTable('Pages');
        $this->assertSame('live', $pages->get(2)->status);
    }

    public function testScheduleSetsScheduledStatus(): void
    {
        $when = new DateTimeImmutable('+2 days')->format('Y-m-d\TH:i');
        $this->post('/cabinet/admin/pages/schedule/2', [
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'body' => '<p>Scheduled.</p>',
            'published_at' => $when,
        ]);

        $this->assertRedirect('/cabinet/admin/pages/edit/2');

        $pages = $this->fetchTable('Pages');
        $page = $pages->get(2);
        $this->assertSame('scheduled', $page->status);
        $this->assertNotNull($page->published_at);
    }

    public function testScheduleRejectsPastDate(): void
    {
        $this->enableRetainFlashMessages();
        $this->post('/cabinet/admin/pages/schedule/2', [
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'body' => '<p>Bad.</p>',
            'published_at' => '2020-01-01T00:00',
        ]);

        $this->assertRedirect('/cabinet/admin/pages/edit/2');
        $this->assertFlashMessage('Scheduled pages need a future date.');
    }

    public function testAutosaveUpdatesDraftFieldsAndDoesNotWriteRevision(): void
    {
        $revisions = $this->fetchTable('PageRevisions');
        $countBefore = $revisions->find()->where(['page_id' => 1])->count();

        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->patch('/cabinet/admin/pages/autosave/1', [
            'title' => 'About — autosaved',
            'slug' => 'about',
            'body' => '<p>Mid-edit.</p>',
        ]);

        $this->assertResponseOk();
        $this->assertNotNull($this->_response);
        $body = json_decode((string) $this->_response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('savedAt', $body);

        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $this->assertSame('About — autosaved', $page->title);

        $countAfter = $revisions->find()->where(['page_id' => 1])->count();
        $this->assertSame($countBefore, $countAfter);
    }

    public function testAutosavePersistsStatusChange(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);

        $this->patch('/cabinet/admin/pages/autosave/1', [
            'title' => 'About',
            'slug' => 'about',
            'body' => '<p>Body.</p>',
            'status' => 'draft',
        ]);

        $this->assertResponseOk();

        $pages = $this->fetchTable('Pages');
        $this->assertSame('draft', $pages->get(1)->status);
    }

    public function testAutosaveIgnoresFieldsOutsideItsAllowList(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);

        $this->patch('/cabinet/admin/pages/autosave/1', [
            'title' => 'About — autosaved',
            'visibility' => 'private',
            'published_at' => '2030-01-01 00:00:00',
        ]);

        $this->assertResponseOk();
        $page = $this->fetchTable('Pages')->get(1);
        $this->assertSame('About — autosaved', $page->title);
        $this->assertSame('public', $page->visibility);
        $this->assertNull($page->published_at);
    }

    public function testAutosave404OnUnknownPage(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->patch('/cabinet/admin/pages/autosave/9999', ['body' => '<p>x</p>']);

        $this->assertResponseCode(404);
    }

    public function testRestoreReplacesContentAndWritesRevision(): void
    {
        $this->post('/cabinet/admin/pages/restore/1/1');

        $this->assertRedirect('/cabinet/admin/pages/edit/1');

        $pages = $this->fetchTable('Pages');
        $page = $pages->get(1);
        $this->assertSame('About Us', $page->title);

        $revisions = $this->fetchTable('PageRevisions');
        $last = $revisions->find()->where(['page_id' => 1])->orderByDesc('version')->firstOrFail();
        $this->assertSame('Restored from v1', $last->note);
    }

    public function testAttachTagCreatesNewTagAndJoin(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/cabinet/admin/pages/1/tags/brand-new', []);

        $this->assertResponseOk();

        $tags = $this->fetchTable('Tags');
        $tag = $tags->find()->where(['slug' => 'brand-new'])->firstOrFail();
        $this->assertSame('brand-new', $tag->slug);

        $joins = $this->fetchTable('Taggables');
        $exists = $joins->exists(['taggable_type' => 'Pages', 'taggable_id' => 1, 'tag_id' => $tag->id]);
        $this->assertTrue($exists);
    }

    public function testDetachTagRemovesJoinKeepsTag(): void
    {
        $this->post('/cabinet/admin/pages/1/tags/heritage/detach', []);

        $joins = $this->fetchTable('Taggables');
        $tags = $this->fetchTable('Tags');

        $this->assertFalse($joins->exists(['taggable_type' => 'Pages', 'taggable_id' => 1, 'tag_id' => 1]));
        $this->assertTrue($tags->exists(['slug' => 'heritage']));
    }

    public function testIndexFiltersByStatus(): void
    {
        // Page titles also appear in the parent-filter dropdown, so assert on the
        // result rows (edit links) rather than the bare title text.
        $this->get('/cabinet/admin/pages?status=draft');

        $this->assertResponseOk();
        $this->assertResponseContains('cms-tree__title" href="/cabinet/admin/pages/edit/2"');
        $this->assertResponseNotContains('cms-tree__title" href="/cabinet/admin/pages/edit/1"');
    }

    public function testIndexFiltersByParent(): void
    {
        // Press (id 3) is a child of About Us (id 1); filtering by that parent
        // shows the child row and not the unrelated root pages.
        $this->get('/cabinet/admin/pages?parent_id=1');

        $this->assertResponseOk();
        $this->assertResponseContains('cms-tree__title" href="/cabinet/admin/pages/edit/3"');
        $this->assertResponseNotContains('cms-tree__title" href="/cabinet/admin/pages/edit/2"');
    }

    public function testReorderPersistsParentAndPosition(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);

        $this->post('/cabinet/admin/pages/reorder', [
            'order' => json_encode([
                ['id' => 2, 'parentId' => 1, 'position' => 0],
                ['id' => 3, 'parentId' => null, 'position' => 1],
            ]),
        ]);

        $this->assertResponseOk();
        $pages = $this->fetchTable('Pages');
        $this->assertSame(1, $pages->get(2)->parent_id);
        $this->assertSame(0, $pages->get(2)->position);
        $this->assertNull($pages->get(3)->parent_id);
    }

    public function testReorderRejectsCycle(): void
    {
        // Press (3) is a child of About Us (1); making 1 a child of 3 is a cycle.
        $this->post('/cabinet/admin/pages/reorder', [
            'order' => json_encode([
                ['id' => 1, 'parentId' => 3, 'position' => 0],
                ['id' => 3, 'parentId' => 1, 'position' => 0],
            ]),
        ]);

        $this->assertResponseCode(400);
        $pages = $this->fetchTable('Pages');
        $this->assertNull($pages->get(1)->parent_id);
    }

    public function testBulkStatusUpdatesSelectedPages(): void
    {
        $this->post('/cabinet/admin/pages/bulk-status', ['ids' => [1, 3], 'status' => 'draft']);

        $this->assertRedirect('/cabinet/admin/pages');
        $pages = $this->fetchTable('Pages');
        $this->assertSame('draft', $pages->get(1)->status);
        $this->assertSame('draft', $pages->get(3)->status);
    }

    public function testBulkStatusRejectsInvalidStatus(): void
    {
        $this->post('/cabinet/admin/pages/bulk-status', ['ids' => [1], 'status' => 'bogus']);

        $this->assertRedirect('/cabinet/admin/pages');
        $pages = $this->fetchTable('Pages');
        $this->assertSame('live', $pages->get(1)->status);
    }

    public function testEditFormRendersActionBarAndRevisions(): void
    {
        $this->get('/cabinet/admin/pages/edit/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Save Draft');
        $this->assertResponseContains('/cabinet/admin/pages/schedule/1');
        // Revisions restore is a JS-driven button carrying the version; it posts
        // to /pages/restore/{id}/{version} via fetch, so no nested form is rendered.
        $this->assertResponseContains('data-restore-version="');
        $this->assertResponseContains('Delete page');
        $this->assertResponseContains('name="comments_enabled"');
        // The slug pattern must be a v-flag-valid regex (escaped hyphen), else
        // browsers reject it: "Invalid character class".
        $this->assertResponseContains('pattern="[a-z0-9\-]+"');
    }
}
