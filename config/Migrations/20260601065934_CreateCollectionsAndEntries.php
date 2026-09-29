<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCollectionsAndEntries extends BaseMigration
{
    public function change(): void
    {
        $collections = $this->table('collections', ['id' => false, 'primary_key' => ['id']]);
        $collections
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('description', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('field_schema', 'json', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_collections_slug'])
            ->create();

        $entries = $this->table('collection_entries', ['id' => false, 'primary_key' => ['id']]);
        $entries
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('collection_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('title', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 160, 'null' => false])
            ->addColumn('data', 'json', ['null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'draft'])
            ->addColumn('published_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('author_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['collection_id', 'slug'], ['unique' => true, 'name' => 'uq_entries_collection_slug'])
            ->addIndex(['collection_id', 'status'], ['name' => 'idx_entries_collection_status'])
            ->addForeignKey('collection_id', 'collections', 'id', [
                'delete' => 'CASCADE', 'update' => 'NO_ACTION',
                'constraint' => 'fk_entries_collection_id',
            ])
            ->addForeignKey('author_id', 'users', 'id', [
                'delete' => 'RESTRICT', 'update' => 'CASCADE',
                'constraint' => 'fk_entries_author_id',
            ])
            ->create();
    }
}
