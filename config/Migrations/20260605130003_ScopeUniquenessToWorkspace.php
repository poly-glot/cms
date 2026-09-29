<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class ScopeUniquenessToWorkspace extends BaseMigration
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
        foreach (self::TENANT_TABLES as $name) {
            $this->execute("ALTER TABLE {$name} MODIFY workspace_id BIGINT UNSIGNED NOT NULL");
        }

        $this->table('pages')->removeIndex(['slug', 'parent_id'])->update();
        $this->execute(
            'ALTER TABLE pages ADD CONSTRAINT uq_pages_workspace_slug_parent UNIQUE (workspace_id, slug, parent_id)',
        );

        $this->swapSlugUnique('posts', 'uq_posts_workspace_slug', 'slug');
        $this->swapSlugUnique('tags', 'uq_tags_workspace_slug', 'slug');
        $this->swapSlugUnique('collections', 'uq_collections_workspace_slug', 'slug');
        $this->swapSlugUnique('menus', 'uq_menus_workspace_slug', 'slug');
        $this->swapSlugUnique('media', 'uq_media_workspace_filename', 'filename');
        $this->swapSlugUnique('settings', 'uq_settings_workspace_key', 'setting_key');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE pages DROP INDEX uq_pages_workspace_slug_parent');
        $this->table('pages')
            ->addIndex(['slug', 'parent_id'], ['unique' => true, 'name' => 'uq_pages_slug_parent'])
            ->update();

        $this->restoreSlugUnique('posts', 'uq_posts_workspace_slug', 'slug', 'uq_posts_slug');
        $this->restoreSlugUnique('tags', 'uq_tags_workspace_slug', 'slug', 'uq_tags_slug');
        $this->restoreSlugUnique('collections', 'uq_collections_workspace_slug', 'slug', 'uq_collections_slug');
        $this->restoreSlugUnique('menus', 'uq_menus_workspace_slug', 'slug', 'uq_menus_slug');
        $this->restoreSlugUnique('media', 'uq_media_workspace_filename', 'filename', 'uq_media_filename');
        $this->restoreSlugUnique('settings', 'uq_settings_workspace_key', 'setting_key', 'uq_settings_setting_key');

        foreach (self::TENANT_TABLES as $name) {
            $this->execute("ALTER TABLE {$name} MODIFY workspace_id BIGINT UNSIGNED NULL");
        }
    }

    private function swapSlugUnique(string $table, string $newIndex, string $column): void
    {
        $this->table($table)->removeIndex([$column])->update();
        $this->execute("ALTER TABLE {$table} ADD CONSTRAINT {$newIndex} UNIQUE (workspace_id, {$column})");
    }

    private function restoreSlugUnique(string $table, string $newIndex, string $column, string $oldIndex): void
    {
        $this->execute("ALTER TABLE {$table} DROP INDEX {$newIndex}");
        $this->table($table)
            ->addIndex([$column], ['unique' => true, 'name' => $oldIndex])
            ->update();
    }
}
