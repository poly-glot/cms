<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateTagsAndPagesTags extends BaseMigration
{
    public function change(): void
    {
        $tags = $this->table('tags', ['id' => false, 'primary_key' => ['id']]);
        $tags
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('label', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_tags_slug'])
            ->create();

        $joins = $this->table('pages_tags', ['id' => false, 'primary_key' => ['page_id', 'tag_id']]);
        $joins
            ->addColumn('page_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('tag_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addForeignKey('page_id', 'pages', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('tag_id', 'tags', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
