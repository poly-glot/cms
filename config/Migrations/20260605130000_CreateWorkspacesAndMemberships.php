<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateWorkspacesAndMemberships extends BaseMigration
{
    public function change(): void
    {
        $workspaces = $this->table('workspaces', ['id' => false, 'primary_key' => ['id']]);
        $workspaces
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_workspaces_slug'])
            ->create();

        $memberships = $this->table('memberships', ['id' => false, 'primary_key' => ['id']]);
        $memberships
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('role', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['workspace_id', 'user_id'], ['unique' => true, 'name' => 'uq_memberships_workspace_user'])
            ->addIndex(['user_id'], ['name' => 'idx_memberships_user_id'])
            ->addForeignKey('workspace_id', 'workspaces', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_memberships_workspace_id',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_memberships_user_id',
            ])
            ->create();
    }
}
