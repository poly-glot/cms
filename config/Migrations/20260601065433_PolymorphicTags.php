<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class PolymorphicTags extends BaseMigration
{
    public function up(): void
    {
        $this->table('pages_tags')
            ->dropForeignKey('page_id')
            ->update();

        $this->table('pages_tags')->rename('taggables')->update();

        $this->table('taggables')
            ->renameColumn('page_id', 'taggable_id')
            ->addColumn('taggable_type', 'string', [
                'limit' => 40,
                'null' => false,
                'default' => 'Pages',
                'after' => 'taggable_id',
            ])
            ->update();

        $this->table('taggables')
            ->changePrimaryKey(['taggable_type', 'taggable_id', 'tag_id'])
            ->update();

        $this->table('taggables')
            ->addIndex(['tag_id'], ['name' => 'idx_taggables_tag_id'])
            ->update();
    }

    public function down(): void
    {
        $this->table('taggables')
            ->removeIndexByName('idx_taggables_tag_id')
            ->update();

        $this->table('taggables')
            ->changePrimaryKey(['taggable_id', 'tag_id'])
            ->update();

        $this->table('taggables')
            ->removeColumn('taggable_type')
            ->renameColumn('taggable_id', 'page_id')
            ->update();

        $this->table('taggables')->rename('pages_tags')->update();

        $this->table('pages_tags')
            ->addForeignKey('page_id', 'pages', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
