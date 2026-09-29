<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddPasswordResetToUsers extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('password_reset_token_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'default' => null,
                'after' => 'accepted_at',
            ])
            ->addColumn('password_reset_expires_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'password_reset_token_hash',
            ])
            ->addIndex(['password_reset_token_hash'], [
                'unique' => true,
                'name' => 'uq_users_password_reset_token_hash',
            ])
            ->update();
    }
}
