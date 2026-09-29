<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class DropUsersRememberToken extends BaseMigration
{
    public function up(): void
    {
        $this->table('users')->removeColumn('remember_token')->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->addColumn('remember_token', 'string', ['limit' => 64, 'null' => true, 'default' => null, 'after' => 'name'])
            ->update();
    }
}
