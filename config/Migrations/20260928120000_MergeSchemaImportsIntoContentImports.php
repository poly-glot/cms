<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class MergeSchemaImportsIntoContentImports extends BaseMigration
{
    public function up(): void
    {
        $this->execute(
            "INSERT INTO content_imports (workspace_id, subject_type, slug, hash, imported_at)
             SELECT workspace_id, 'Collections', slug, hash, imported_at
             FROM collection_schema_imports",
        );

        $this->table('collection_schema_imports')->drop()->save();
    }

    public function down(): void
    {
        $this->table('collection_schema_imports', ['id' => false, 'primary_key' => ['id']])
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

        $this->execute(
            "INSERT INTO collection_schema_imports (workspace_id, slug, hash, imported_at)
             SELECT workspace_id, slug, hash, imported_at
             FROM content_imports
             WHERE subject_type = 'Collections'",
        );

        $this->execute("DELETE FROM content_imports WHERE subject_type = 'Collections'");
    }
}
