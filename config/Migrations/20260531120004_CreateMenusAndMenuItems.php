<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateMenusAndMenuItems extends BaseMigration
{
    public function change(): void
    {
        $menus = $this->table('menus', ['id' => false, 'primary_key' => ['id']]);
        $menus
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_menus_slug'])
            ->create();

        $items = $this->table('menu_items', ['id' => false, 'primary_key' => ['id']]);
        $items
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('menu_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('parent_id', 'biginteger', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('position', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('title', 'string', ['limit' => 160, 'null' => false])
            ->addColumn('type', 'string', ['limit' => 8, 'null' => false, 'default' => 'page'])
            ->addColumn('page_id', 'biginteger', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('url', 'string', ['limit' => 2048, 'null' => true, 'default' => null])
            ->addColumn('target', 'string', ['limit' => 8, 'null' => false, 'default' => '_self'])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['menu_id', 'parent_id', 'position'], ['name' => 'idx_menu_items_tree'])
            ->addIndex(['page_id'], ['name' => 'idx_menu_items_page_id'])
            ->addForeignKey('menu_id', 'menus', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_menu_items_menu_id',
            ])
            ->addForeignKey('parent_id', 'menu_items', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_menu_items_parent_id',
            ])
            ->addForeignKey('page_id', 'pages', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_menu_items_page_id',
            ])
            ->create();
    }
}
