<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateComments extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('comments', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('page_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('parent_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('author_name', 'string', [
                'limit' => 120,
                'null' => false,
            ])
            ->addColumn('author_email', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('body', 'text', [
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'pending',
            ])
            ->addColumn('is_read', 'boolean', [
                'null' => false,
                'default' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['page_id', 'status'], ['name' => 'idx_comments_page_status'])
            ->addIndex(['parent_id'], ['name' => 'idx_comments_parent_id'])
            ->addIndex(['status'], ['name' => 'idx_comments_status'])
            ->addForeignKey('page_id', 'pages', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_comments_page_id',
            ])
            ->addForeignKey('parent_id', 'comments', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_comments_parent_id',
            ])
            ->create();
    }
}
