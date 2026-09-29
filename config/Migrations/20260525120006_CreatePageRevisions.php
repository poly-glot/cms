<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePageRevisions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('page_revisions', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('page_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('version', 'smallinteger', ['signed' => false, 'null' => false])
            ->addColumn('author_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('title', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 160, 'null' => false])
            ->addColumn('body', 'text', ['null' => true, 'default' => null])
            ->addColumn('status', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('template', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('published_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('parent_id', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('visibility', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('note', 'string', ['limit' => 120, 'null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['page_id', 'version'], ['unique' => true, 'name' => 'uq_revisions_page_version'])
            ->addIndex(['page_id', 'created'], ['name' => 'idx_revisions_page_created'])
            ->addForeignKey('page_id', 'pages', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('author_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
