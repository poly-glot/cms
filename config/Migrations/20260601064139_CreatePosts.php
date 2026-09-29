<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePosts extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('posts', ['id' => false, 'primary_key' => ['id']]);
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
            ->addColumn('excerpt', 'string', [
                'limit' => 500,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('body', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'draft',
            ])
            ->addColumn('published_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('comments_enabled', 'boolean', [
                'null' => false,
                'default' => false,
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
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_posts_slug'])
            ->addIndex(['author_id'], ['name' => 'idx_posts_author_id'])
            ->addIndex(['status', 'published_at'], ['name' => 'idx_posts_status_published_at'])
            ->addForeignKey('author_id', 'users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_posts_author_id',
            ])
            ->create();
    }
}
