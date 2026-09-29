<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class BackfillDefaultWorkspace extends BaseMigration
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

    public function up(): void
    {
        $this->execute(
            "INSERT INTO workspaces (name, slug, created, modified) VALUES ('Cabinet', 'cabinet', NOW(), NOW())",
        );

        $this->execute(
            'INSERT INTO memberships (workspace_id, user_id, role, created, modified)
             SELECT w.id, u.id, u.role, NOW(), NOW()
             FROM users u
             CROSS JOIN (SELECT id FROM workspaces ORDER BY id ASC LIMIT 1) w',
        );

        foreach (self::TENANT_TABLES as $name) {
            $this->execute(
                "UPDATE {$name}
                 SET workspace_id = (SELECT id FROM workspaces ORDER BY id ASC LIMIT 1)
                 WHERE workspace_id IS NULL",
            );
        }
    }

    public function down(): void
    {
        foreach (self::TENANT_TABLES as $name) {
            $this->execute("UPDATE {$name} SET workspace_id = NULL");
        }

        $this->execute('DELETE FROM memberships');
        $this->execute("DELETE FROM workspaces WHERE slug = 'cabinet'");
    }
}
