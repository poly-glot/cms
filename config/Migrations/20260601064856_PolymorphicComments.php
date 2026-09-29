<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class PolymorphicComments extends BaseMigration
{
    public function up(): void
    {
        $this->table('comments')
            ->addColumn('commentable_type', 'string', [
                'limit' => 40,
                'null' => false,
                'default' => 'Pages',
                'after' => 'id',
            ])
            ->addColumn('commentable_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'commentable_type',
            ])
            ->update();

        $this->execute('UPDATE comments SET commentable_id = page_id WHERE page_id IS NOT NULL');

        $this->table('comments')
            ->dropForeignKey('page_id')
            ->removeIndexByName('idx_comments_page_status')
            ->update();

        $this->table('comments')
            ->changeColumn('commentable_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->removeColumn('page_id')
            ->addIndex(['commentable_type', 'commentable_id', 'status'], [
                'name' => 'idx_comments_commentable_status',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('comments')
            ->addColumn('page_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'after' => 'id',
            ])
            ->update();

        $this->execute("UPDATE comments SET page_id = commentable_id WHERE commentable_type = 'Pages'");

        $this->table('comments')
            ->changeColumn('page_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->removeIndexByName('idx_comments_commentable_status')
            ->removeColumn('commentable_type')
            ->removeColumn('commentable_id')
            ->addIndex(['page_id', 'status'], ['name' => 'idx_comments_page_status'])
            ->addForeignKey('page_id', 'pages', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_comments_page_id',
            ])
            ->update();
    }
}
