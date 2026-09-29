<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateMediaRenditions extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('media_renditions', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('media_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'limit' => 16,
                'null' => false,
            ])
            ->addColumn('filename', 'string', [
                'limit' => 255,
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
            ->addColumn('size', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['media_id', 'name'], ['unique' => true, 'name' => 'uq_media_renditions_media_name'])
            ->addForeignKey('media_id', 'media', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_media_renditions_media_id',
            ])
            ->create();
    }
}
