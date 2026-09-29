<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateContentImports extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('content_imports', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('subject_type', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 190, 'null' => false])
            ->addColumn('hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('imported_at', 'datetime', ['null' => false])
            ->addIndex(['workspace_id', 'subject_type', 'slug'], ['unique' => true, 'name' => 'uq_content_imports_workspace_subject_slug'])
            ->addForeignKey('workspace_id', 'workspaces', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_content_imports_workspace_id',
            ])
            ->create();
    }
}
