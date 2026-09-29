<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBlocksAndPageBlocks extends BaseMigration
{
    public function change(): void
    {
        $blocks = $this->table('blocks', ['id' => false, 'primary_key' => ['id']]);
        $blocks
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('block_type', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('data', 'json', ['null' => false])
            ->addColumn('author_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['block_type'], ['name' => 'idx_blocks_type'])
            ->addForeignKey('author_id', 'users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();

        $pageBlocks = $this->table('page_blocks', ['id' => false, 'primary_key' => ['page_id', 'block_id']]);
        $pageBlocks
            ->addColumn('page_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('block_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addForeignKey('page_id', 'pages', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('block_id', 'blocks', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }
}
