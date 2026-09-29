<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddWorkspaceIdToTenantTables extends BaseMigration
{
    private const array TENANT_TABLES = [
        'pages',
        'posts',
        'collections',
        'collection_entries',
        'media',
        'media_renditions',
        'blocks',
        'page_blocks',
        'page_revisions',
        'comments',
        'tags',
        'taggables',
        'menus',
        'menu_items',
        'settings',
    ];

    public function change(): void
    {
        foreach (self::TENANT_TABLES as $name) {
            $this->table($name)
                ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => true, 'default' => null])
                ->addIndex(['workspace_id'], ['name' => "idx_{$name}_workspace_id"])
                ->addForeignKey('workspace_id', 'workspaces', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => "fk_{$name}_workspace_id",
                ])
                ->update();
        }
    }
}
