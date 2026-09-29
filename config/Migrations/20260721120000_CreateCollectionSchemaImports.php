<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCollectionSchemaImports extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('collection_schema_imports', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('imported_at', 'datetime', ['null' => false])
            ->addIndex(['workspace_id', 'slug'], ['unique' => true, 'name' => 'uq_schema_imports_workspace_slug'])
            ->addForeignKey('workspace_id', 'workspaces', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_schema_imports_workspace_id',
            ])
            ->create();
    }
}
