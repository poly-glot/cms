<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddParentAndPositionToPages extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('pages');
        $table
            ->addColumn('parent_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'author_id',
            ])
            ->addColumn('position', 'smallinteger', [
                'signed' => false,
                'null' => false,
                'default' => 0,
                'after' => 'parent_id',
            ])
            ->addIndex(['parent_id', 'position'], ['name' => 'idx_pages_parent_position'])
            ->addForeignKey('parent_id', 'pages', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }
}
