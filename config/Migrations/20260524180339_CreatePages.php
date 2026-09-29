<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePages extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('pages', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'limit' => 200,
                'null' => false,
            ])
            ->addColumn('slug', 'string', [
                'limit' => 160,
                'null' => false,
            ])
            ->addColumn('body', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('status', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'draft',
            ])
            ->addColumn('author_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_pages_slug'])
            ->addIndex(['author_id'], ['name' => 'idx_pages_author_id'])
            ->addForeignKey('author_id', 'users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_pages_author_id',
            ])
            ->create();
    }
}
