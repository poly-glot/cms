<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class DropUsersRole extends BaseMigration
{
    public function up(): void
    {
        $this->table('users')->removeColumn('role')->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->addColumn('role', 'string', ['limit' => 20, 'null' => false, 'default' => 'author'])
            ->update();
    }
}
