<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\MenuItem;
use App\Model\Entity\Page;
use Cake\Cache\Cache;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\Exception\PersistenceFailedException;

final class NavigationController extends AdminController
{
    public function index(): void
    {
        $menuItems = $this->fetchTable('MenuItems');
        $menus = $this->fetchTable('Menus')->find()->orderByAsc('Menus.id')->all();

        $menuTrees = [];
        $tabs = [];
        foreach ($menus as $menu) {
            $tree = $menuItems->find('tree', menuId: $menu->id)->all()->toList();
            $menuTrees[$menu->slug] = array_map($this->nodeArray(...), $tree);
            $tabs[] = ['slug' => $menu->slug, 'name' => $menu->name];
        }

        $this->set([
            'tabs' => $tabs,
            'navData' => [
                'menus' => $menuTrees,
                'pages' => $this->pageOptions(),
                'active' => $tabs[0]['slug'] ?? null,
            ],
        ]);
    }

    public function save(): Response
    {
        $this->request->allowMethod('post');
        $slug = $this->request->getData('menu');
        $menu = $this->fetchTable('Menus')->find()->where(['Menus.slug' => $slug])->first()
            ?? throw new NotFoundException();
        $this->Authorization->authorize($menu, 'manage');

        $payload = $this->request->getData('tree');
        $tree = is_string($payload) ? json_decode($payload, true) : null;
        if (!is_array($tree)) {
            return $this->json(['error' => 'Malformed menu data.'], 400);
        }

        try {
            $this->fetchTable('MenuItems')->rebuild($menu, $tree);
        } catch (PersistenceFailedException) {
            return $this->json(['error' => 'Some menu items are invalid and were not saved.'], 422);
        }

        Cache::clear('menus');

        return $this->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function nodeArray(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'label' => $item->title,
            'pageId' => $item->page_id,
            'url' => $item->url,
            'target' => $item->target,
            'children' => array_map($this->nodeArray(...), $item->children),
        ];
    }

    /**
     * @return list<array{id: int, title: string, path: string}>
     */
    private function pageOptions(): array
    {
        $pages = $this->fetchTable('Pages');
        $paths = $pages->pathsById();

        return array_values(array_map(
            static fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'path' => '/' . $paths[$page->id],
            ],
            $pages->find()->select(['id', 'title'])->orderByAsc('Pages.title')->all()->toList(),
        ));
    }
}
