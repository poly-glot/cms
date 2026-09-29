<?php

declare(strict_types=1);

namespace App\Test\TestCase\Tenancy;

use App\Model\Enum\UserRole;
use App\Model\Table\CommentsTable;
use App\Model\Table\MenuItemsTable;
use App\Model\Table\PageBlocksTable;
use App\Model\Table\PagesTable;
use App\Model\Tenancy\TenantContext;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\TestCase;
use Override;

final class IsolationTest extends TestCase
{
    /** @var array<int, string> */
    protected array $fixtures = [
        'app.Users',
        'app.Pages',
        'app.Blocks',
        'app.PageBlocks',
        'app.Menus',
        'app.MenuItems',
        'app.Comments',
    ];

    private PagesTable $Pages;

    private CommentsTable $Comments;

    private MenuItemsTable $MenuItems;

    private PageBlocksTable $PageBlocks;

    private TenantContext $context;

    private int $foreignPageId;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->Pages = $this->fetchTable('Pages');
        $this->Comments = $this->fetchTable('Comments');
        $this->MenuItems = $this->fetchTable('MenuItems');
        $this->PageBlocks = $this->fetchTable('PageBlocks');
        $this->context = TenantContext::instance();
        $this->foreignPageId = $this->createForeignPage();
    }

    public function testFindIsScopedAcrossWorkspaces(): void
    {
        $result = $this->Pages->find()->where(['Pages.id' => $this->foreignPageId])->first();

        $this->assertNull($result);
    }

    public function testExistsIsScopedAcrossWorkspaces(): void
    {
        $this->assertFalse($this->Pages->exists(['Pages.id' => $this->foreignPageId]));
    }

    public function testRejectsParentIdInAnotherWorkspace(): void
    {
        $child = $this->Pages->newEntity([
            'title' => 'Child of Foreign',
            'slug' => 'child-of-foreign',
            'body' => '<p>x</p>',
            'status' => 'draft',
            'visibility' => 'public',
            'template' => 'default',
            'parent_id' => $this->foreignPageId,
            'author_id' => 1,
        ]);

        $this->assertFalse($this->Pages->save($child));
        $this->assertArrayHasKey('parent_id', $child->getErrors());
    }

    public function testRejectsCommentTargetingForeignPage(): void
    {
        $comment = $this->Comments->newEntity([
            'commentable_type' => 'Pages',
            'commentable_id' => $this->foreignPageId,
            'author_name' => 'Stranger',
            'author_email' => 'stranger@example.com',
            'body' => 'Cross-tenant comment attempt.',
        ]);

        $this->assertFalse($this->Comments->save($comment));
        $this->assertArrayHasKey('commentable_id', $comment->getErrors());
    }

    public function testRejectsMenuItemPointingAtForeignPage(): void
    {
        $menuItem = $this->MenuItems->newEntity([
            'menu_id' => 1,
            'parent_id' => null,
            'position' => 99,
            'title' => 'Foreign Link',
            'type' => 'page',
            'page_id' => $this->foreignPageId,
            'target' => '_self',
        ]);

        $this->assertFalse($this->MenuItems->save($menuItem));
        $this->assertArrayHasKey('page_id', $menuItem->getErrors());
    }

    public function testBulkDeleteDoesNotCrossWorkspaces(): void
    {
        $foreignBlocksBefore = $this->context->runScoped(
            2,
            UserRole::Admin,
            fn (): int => $this->PageBlocks->find()->where(['page_id' => $this->foreignPageId])->count(),
        );

        $this->PageBlocks->deleteAll(['page_id' => $this->foreignPageId, 'workspace_id' => 1]);

        $foreignBlocksAfter = $this->context->runScoped(
            2,
            UserRole::Admin,
            fn (): int => $this->PageBlocks->find()->where(['page_id' => $this->foreignPageId])->count(),
        );

        $this->assertSame($foreignBlocksBefore, $foreignBlocksAfter);
    }

    private function createForeignPage(): int
    {
        $page = $this->context->runScoped(2, UserRole::Admin, function (): EntityInterface {
            $page = $this->Pages->newEntity([
                'title' => 'Foreign Workspace Page',
                'slug' => 'foreign-page',
                'body' => '<p>Belongs to workspace 2.</p>',
                'status' => 'live',
                'visibility' => 'public',
                'template' => 'default',
                'parent_id' => null,
                'position' => 0,
                'author_id' => 1,
            ]);

            return $this->Pages->saveOrFail($page);
        });

        $this->context->runScoped(2, UserRole::Admin, function () use ($page): void {
            $link = $this->PageBlocks->newEmptyEntity();
            $link->page_id = $page->id;
            $link->block_id = 1;
            $this->PageBlocks->saveOrFail($link);
        });

        return (int) $page->id;
    }
}
