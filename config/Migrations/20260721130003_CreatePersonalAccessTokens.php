<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePersonalAccessTokens extends BaseMigration
{
    public function change(): void
    {
        $tokens = $this->table('personal_access_tokens', ['id' => false, 'primary_key' => ['id']]);
        $tokens
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('workspace_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('scopes', 'json', ['null' => false])
            ->addColumn('last_used_at', 'datetime', ['null' => true])
            ->addColumn('expires_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['token_hash'], ['unique' => true, 'name' => 'uq_pat_token_hash'])
            ->addIndex(['workspace_id', 'user_id'], ['name' => 'idx_pat_workspace_user'])
            ->addForeignKey('workspace_id', 'workspaces', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_pat_workspace_id',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_pat_user_id',
            ])
            ->create();
    }
}
