<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateContentTypeFieldSchemas extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('content_type_field_schemas', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('subject_type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('field_schema', 'json', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['workspace_id', 'subject_type'], ['unique' => true, 'name' => 'uq_ctfs_workspace_subject'])
            ->addForeignKey('workspace_id', 'workspaces', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_ctfs_workspace_id',
            ])
            ->create();
    }
}
