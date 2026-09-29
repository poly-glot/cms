<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateMedia extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('media', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('filename', 'char', [
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('mime', 'string', [
                'limit' => 120,
                'null' => false,
            ])
            ->addColumn('size', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('width', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('height', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('alt', 'string', [
                'limit' => 500,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('uploaded_by', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['filename'], ['unique' => true, 'name' => 'uq_media_filename'])
            ->addIndex(['uploaded_by'], ['name' => 'idx_media_uploaded_by'])
            ->addForeignKey('uploaded_by', 'users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_media_uploaded_by',
            ])
            ->create();
    }
}
