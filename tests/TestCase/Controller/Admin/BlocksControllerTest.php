<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class BlocksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Users', 'app.Memberships', 'app.Media', 'app.Pages', 'app.Blocks', 'app.PageBlocks'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->session(['Auth' => ['id' => 1, 'email' => 'admin@cabinet.local', 'name' => 'Admin', 'role' => 'admin']]);
    }

    public function testIndexRequiresAuthentication(): void
    {
        $this->_session = [];
        $this->get('/cabinet/admin/blocks');

        $this->assertRedirectContains('/login');
    }

    public function testIndexRenders(): void
    {
        $this->get('/cabinet/admin/blocks');

        $this->assertResponseOk();
        $this->assertResponseContains('Hours note');
    }

    public function testListReturnsJson(): void
    {
        $this->get('/cabinet/admin/blocks/list');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertResponseContains('"name":"Hours note"');
    }

    public function testAddWithoutTypeRendersChooser(): void
    {
        $this->get('/cabinet/admin/blocks/add');

        $this->assertResponseOk();
        $this->assertResponseContains('cms-block-type-tile');
        $this->assertResponseNotContains('data-block-preview');
    }

    public function testAddWithValidTypeRendersEditor(): void
    {
        $this->get('/cabinet/admin/blocks/add?type=callout');

        $this->assertResponseOk();
        $this->assertResponseContains('data-block-form');
        $this->assertResponseContains('data-block-preview');
        $this->assertResponseContains('Create block');
        $this->assertResponseNotContains('cms-block-type-tile');
    }

    public function testAddWithInvalidTypeFallsBackToChooser(): void
    {
        $this->get('/cabinet/admin/blocks/add?type=bogus');

        $this->assertResponseOk();
        $this->assertResponseContains('cms-block-type-tile');
        $this->assertResponseNotContains('data-block-preview');
    }

    public function testEditImageBlockRendersWorkspacePrefixedPreview(): void
    {
        $this->get('/cabinet/admin/blocks/edit/3');

        $this->assertResponseOk();
        $this->assertResponseContains('data-block-preview');
        $this->assertResponseContains('/cabinet/admin/media/serve/1?rendition=large');
    }

    public function testAddCreatesBlock(): void
    {
        $this->post('/cabinet/admin/blocks/add', [
            'block_type' => 'callout',
            'name' => 'Reminder',
            'data' => ['variant' => 'warning', 'body' => 'Closing early.'],
        ]);

        $this->assertResponseSuccess();
        $blocks = $this->fetchTable('Blocks');
        $this->assertSame(1, $blocks->find()->where(['name' => 'Reminder'])->count());
    }

    public function testAddRejectsInvalidData(): void
    {
        $blocks = $this->fetchTable('Blocks');
        $before = $blocks->find()->count();

        $this->post('/cabinet/admin/blocks/add', [
            'block_type' => 'callout',
            'name' => 'Broken',
            'data' => ['variant' => 'warning'],
        ]);

        $this->assertSame($before, $blocks->find()->count());
    }

    public function testEditUpdatesBlock(): void
    {
        $this->post('/cabinet/admin/blocks/edit/1', [
            'block_type' => 'callout',
            'name' => 'Renamed hours',
            'data' => ['variant' => 'info', 'body' => 'Open Mon-Fri.'],
        ]);

        $this->assertResponseSuccess();
        $blocks = $this->fetchTable('Blocks');
        $this->assertSame('Renamed hours', $blocks->get(1)->name);
    }

    public function testDeleteInUseIsBlocked(): void
    {
        $this->post('/cabinet/admin/blocks/delete/1');

        $this->assertRedirect(['action' => 'index']);
        $blocks = $this->fetchTable('Blocks');
        $this->assertTrue($blocks->exists(['id' => 1]));
    }

    public function testDeleteUnreferencedBlock(): void
    {
        $this->post('/cabinet/admin/blocks/delete/2');

        $this->assertRedirect(['action' => 'index']);
        $blocks = $this->fetchTable('Blocks');
        $this->assertFalse($blocks->exists(['id' => 2]));
    }
}
