<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Menu;
use App\Model\Entity\MenuItem;
use App\Model\Enum\MenuItemType;
use App\Model\Validation\SafeUrl;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @extends Table<array{}, MenuItem>
 */
final class MenuItemsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Tenant');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Menus', ['joinType' => 'INNER']);
        $this->belongsTo('Pages');
        $this->belongsTo('ParentMenuItems', ['className' => 'MenuItems', 'foreignKey' => 'parent_id']);
    }

    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('title', 'create')
            ->notEmptyString('title')
            ->maxLength('title', 160);

        $validator->enum('type', MenuItemType::class);
        $validator->inList('target', ['_self', '_blank']);
        $validator->maxLength('url', 2048);

        $validator->allowEmptyString('page_id', 'Choose a page for this menu item.', fn (array $context): bool => $this->contextType($context) === 'url');
        $validator->allowEmptyString('url', 'Enter a URL for this link.', fn (array $context): bool => $this->contextType($context) === 'page');
        $validator->add('url', 'safeUrl', [
            'rule' => static fn (mixed $value): bool => !is_string($value) || $value === '' || SafeUrl::check($value),
            'message' => 'Enter a valid URL (http, https, mailto, or a site path beginning with /).',
        ]);

        return $validator;
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function contextType(array $context): string
    {
        $data = $context['data'] ?? [];

        return is_array($data) && ($data['type'] ?? null) === 'url' ? 'url' : 'page';
    }

    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['menu_id'], 'Menus'), ['errorField' => 'menu_id']);
        $rules->add($rules->existsIn(['page_id'], 'Pages'), ['errorField' => 'page_id', 'allowNullableNulls' => true]);
        $rules->add(
            $rules->existsIn(['parent_id'], 'ParentMenuItems'),
            ['errorField' => 'parent_id', 'allowNullableNulls' => true],
        );

        return $rules;
    }

    /**
     * @param SelectQuery<MenuItem> $query
     * @return SelectQuery<MenuItem>
     */
    public function findTree(SelectQuery $query, int $menuId): SelectQuery
    {
        return $query
            ->where(['MenuItems.menu_id' => $menuId])
            ->contain(['Pages'])
            ->orderByAsc('MenuItems.position')
            ->find('threaded');
    }

    /**
     * @param array<array-key, mixed> $tree
     */
    public function rebuild(Menu $menu, array $tree): void
    {
        $this->getConnection()->transactional(function () use ($menu, $tree): void {
            $this->deleteAll(['menu_id' => $menu->id, 'workspace_id' => $menu->workspace_id]);
            $this->insertTree($tree, $menu->id, null);
        });
    }

    /**
     * @param array<array-key, mixed> $nodes
     */
    private function insertTree(array $nodes, int $menuId, ?int $parentId): void
    {
        $position = 0;
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $type = ($node['type'] ?? 'page') === 'url' ? 'url' : 'page';
            $pageId = $node['pageId'] ?? null;

            $item = $this->newEntity([
                'menu_id' => $menuId,
                'parent_id' => $parentId,
                'position' => $position++,
                'title' => is_string($node['label'] ?? null) ? $node['label'] : '',
                'type' => $type,
                'page_id' => $type === 'page' && is_numeric($pageId) ? (int) $pageId : null,
                'url' => $type === 'url' && is_string($node['url'] ?? null) ? $node['url'] : null,
                'target' => ($node['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
            ]);

            $saved = $this->saveOrFail($item);

            $children = $node['children'] ?? [];
            if (is_array($children) && $children !== []) {
                $this->insertTree($children, $menuId, (int) $saved->id);
            }
        }
    }
}
