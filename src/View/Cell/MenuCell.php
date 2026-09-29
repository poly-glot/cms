<?php

declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Entity\Menu;
use App\Model\Entity\MenuItem;
use App\Model\Tenancy\TenantContext;
use Cake\Cache\Cache;
use Cake\View\Cell;
use Cake\View\View;

/**
 * Renders a public navigation menu by slug as a nested list. The resolved link
 * tree (labels + absolute URLs) is cached under "menu_<slug>"; the admin save
 * action busts that key. Page-type items only appear when their target page is
 * live, so menus never link to unpublished content.
 *
 * @extends Cell<View>
 */
final class MenuCell extends Cell
{
    public function display(string $slug): void
    {
        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $links = Cache::remember($workspaceId . '_' . $slug, fn (): array => $this->buildLinks($slug), 'menus');
        $this->set(['links' => $links]);
    }

    /**
     * @return list<array{label: string, url: string, target: string, children: array<mixed>}>
     */
    private function buildLinks(string $slug): array
    {
        $menu = $this->fetchTable('Menus')->findBySlug($slug)->first();
        if (!$menu instanceof Menu) {
            return [];
        }

        $items = $this->fetchTable('MenuItems')->find('tree', menuId: $menu->id)->all()->toList();

        return $this->toLinks($items, $this->fetchTable('Pages')->pathsById());
    }

    /**
     * @param array<MenuItem> $items
     * @param array<int, string> $paths
     * @return list<array{label: string, url: string, target: string, children: array<mixed>}>
     */
    private function toLinks(array $items, array $paths): array
    {
        $links = [];
        foreach ($items as $item) {
            if ($item->type === 'page') {
                if ($item->page === null || $item->page->status !== 'live') {
                    continue;
                }
                $url = '/' . $paths[$item->page->id];
            } else {
                $url = (string) $item->url;
            }

            $links[] = [
                'label' => $item->title,
                'url' => $url,
                'target' => $item->target,
                'children' => $this->toLinks($item->children, $paths),
            ];
        }

        return $links;
    }
}
