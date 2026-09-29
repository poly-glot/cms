<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCollectionEntryReferences extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('collection_entry_references', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('source_entry_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('field_name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('target_entry_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('position', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['source_entry_id', 'field_name', 'target_entry_id'], ['unique' => true, 'name' => 'uq_cer_edge'])
            ->addIndex(['target_entry_id'], ['name' => 'idx_cer_target'])
            ->addIndex(['source_entry_id', 'field_name', 'position'], ['name' => 'idx_cer_source'])
            ->addIndex(['workspace_id'], ['name' => 'idx_cer_workspace_id'])
            ->addForeignKey('workspace_id', 'workspaces', 'id', [
                'delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_cer_workspace_id',
            ])
            ->addForeignKey('source_entry_id', 'collection_entries', 'id', [
                'delete' => 'CASCADE', 'update' => 'NO_ACTION', 'constraint' => 'fk_cer_source',
            ])
            ->addForeignKey('target_entry_id', 'collection_entries', 'id', [
                'delete' => 'RESTRICT', 'update' => 'NO_ACTION', 'constraint' => 'fk_cer_target',
            ])
            ->create();
    }
}
