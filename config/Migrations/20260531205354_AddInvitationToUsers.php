<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddInvitationToUsers extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('invitation_token_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'default' => null,
                'after' => 'remember_token',
            ])
            ->addColumn('invitation_expires_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'invitation_token_hash',
            ])
            ->addColumn('invited_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'invitation_expires_at',
            ])
            ->addColumn('accepted_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'invited_at',
            ])
            ->addIndex(['invitation_token_hash'], [
                'unique' => true,
                'name' => 'uq_users_invitation_token_hash',
            ])
            ->changeColumn('password', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
            ])
            ->update();
    }
}
