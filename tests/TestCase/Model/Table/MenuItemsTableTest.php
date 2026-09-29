<?php

declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\ORM\Exception\PersistenceFailedException;
use Cake\TestSuite\TestCase;

final class MenuItemsTableTest extends TestCase
{
    protected array $fixtures = ['app.Users', 'app.Pages', 'app.Menus', 'app.MenuItems'];

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function urlItem(array $overrides = []): array
    {
        return $overrides + [
            'menu_id' => 1,
            'title' => 'Link',
            'type' => 'url',
            'url' => 'https://example.com',
            'target' => '_self',
        ];
    }

    public function testAllowsHttpHttpsMailtoAndSiteRelativeUrls(): void
    {
        foreach (['https://example.com', 'http://example.com', 'mailto:hi@example.com', '/about'] as $url) {
            $item = $this->fetchTable('MenuItems')->newEntity($this->urlItem(['url' => $url]));
            $this->assertSame([], $item->getError('url'), "Expected {$url} to be accepted.");
        }
    }

    public function testRejectsProtocolRelativeUrl(): void
    {
        $item = $this->fetchTable('MenuItems')->newEntity($this->urlItem(['url' => '//evil.example.com']));

        $this->assertNotSame([], $item->getError('url'));
    }

    public function testRejectsDangerousSchemes(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,x', 'file:///etc/passwd'] as $url) {
            $item = $this->fetchTable('MenuItems')->newEntity($this->urlItem(['url' => $url]));
            $this->assertNotSame([], $item->getError('url'), "Expected {$url} to be rejected.");
        }
    }

    public function testPageItemRequiresPageId(): void
    {
        $item = $this->fetchTable('MenuItems')->newEntity([
            'menu_id' => 1,
            'title' => 'About',
            'type' => 'page',
            'page_id' => null,
            'target' => '_self',
        ]);

        $this->assertNotSame([], $item->getError('page_id'));
    }

    public function testUrlItemRequiresUrl(): void
    {
        $item = $this->fetchTable('MenuItems')->newEntity([
            'menu_id' => 1,
            'title' => 'Link',
            'type' => 'url',
            'url' => null,
            'target' => '_self',
        ]);

        $this->assertNotSame([], $item->getError('url'));
    }

    public function testRebuildReplacesTreeWithPositionsAndParents(): void
    {
        $menus = $this->fetchTable('Menus');
        $menuItems = $this->fetchTable('MenuItems');
        $menu = $menus->get(1);

        $this->fetchTable('MenuItems')->rebuild($menu, [
            ['type' => 'url', 'label' => 'Home', 'url' => '/', 'target' => '_self', 'children' => [
                ['type' => 'page', 'label' => 'About', 'pageId' => 1, 'target' => '_blank', 'children' => []],
            ]],
            ['type' => 'page', 'label' => 'Contact', 'pageId' => 2, 'target' => '_self', 'children' => []],
        ]);

        $items = $menuItems->find()->where(['menu_id' => 1])->orderByAsc('position')->all()->toList();
        $this->assertCount(3, $items);

        $roots = array_values(array_filter($items, static fn ($i): bool => $i->parent_id === null));
        $this->assertSame('Home', $roots[0]->title);
        $this->assertSame(0, $roots[0]->position);
        $this->assertSame('Contact', $roots[1]->title);
        $this->assertSame(1, $roots[1]->position);

        $about = $menuItems->find()->where(['title' => 'About'])->firstOrFail();
        $this->assertSame($roots[0]->id, $about->parent_id);
        $this->assertSame(1, $about->page_id);
        $this->assertSame('_blank', $about->target);
    }

    public function testRebuildIsAtomicWhenANodeIsInvalid(): void
    {
        $menus = $this->fetchTable('Menus');
        $menuItems = $this->fetchTable('MenuItems');
        $menu = $menus->get(1);
        $before = $menuItems->find()->where(['menu_id' => 1])->count();

        $rejected = false;
        try {
            $this->fetchTable('MenuItems')->rebuild($menu, [
                ['type' => 'url', 'label' => 'Evil', 'url' => 'javascript:alert(1)', 'target' => '_self', 'children' => []],
            ]);
        } catch (PersistenceFailedException) {
            $rejected = true;
        }

        $this->assertTrue($rejected);
        $this->assertSame($before, $menuItems->find()->where(['menu_id' => 1])->count());
    }

    public function testAssignsContiguousPositionsWithinEachSiblingGroup(): void
    {
        $menus = $this->fetchTable('Menus');
        $menuItems = $this->fetchTable('MenuItems');

        $this->fetchTable('MenuItems')->rebuild($menus->get(1), [
            ['type' => 'url', 'label' => 'Parent', 'url' => '/p', 'target' => '_self', 'children' => [
                ['type' => 'url', 'label' => 'A', 'url' => '/a', 'target' => '_self', 'children' => []],
                ['type' => 'url', 'label' => 'B', 'url' => '/b', 'target' => '_self', 'children' => []],
            ]],
            ['type' => 'url', 'label' => 'Sibling', 'url' => '/s', 'target' => '_self', 'children' => []],
        ]);

        $parent = $menuItems->find()->where(['title' => 'Parent'])->firstOrFail();
        $sibling = $menuItems->find()->where(['title' => 'Sibling'])->firstOrFail();
        $childA = $menuItems->find()->where(['title' => 'A'])->firstOrFail();
        $childB = $menuItems->find()->where(['title' => 'B'])->firstOrFail();

        $this->assertSame(0, $parent->position);
        $this->assertSame(1, $sibling->position);
        $this->assertSame(0, $childA->position, 'Positions reset to 0 within each sibling group.');
        $this->assertSame(1, $childB->position);
        $this->assertSame($parent->id, $childA->parent_id);
    }

    public function testCoercesNumericStringPageIdToInt(): void
    {
        $menus = $this->fetchTable('Menus');
        $menuItems = $this->fetchTable('MenuItems');

        $this->fetchTable('MenuItems')->rebuild($menus->get(1), [
            ['type' => 'page', 'label' => 'About', 'pageId' => '1', 'target' => '_self', 'children' => []],
        ]);

        $item = $menuItems->find()->where(['title' => 'About'])->firstOrFail();
        $this->assertSame(1, $item->page_id);
    }

    public function testSkipsNonArrayNodes(): void
    {
        $menus = $this->fetchTable('Menus');
        $menuItems = $this->fetchTable('MenuItems');

        $this->fetchTable('MenuItems')->rebuild($menus->get(1), [
            'not-an-array',
            ['type' => 'url', 'label' => 'Real', 'url' => '/', 'target' => '_self', 'children' => []],
        ]);

        $this->assertSame(1, $menuItems->find()->where(['menu_id' => 1])->count());
        $this->assertSame(0, $menuItems->find()->where(['title' => 'Real'])->firstOrFail()->position);
    }
}
