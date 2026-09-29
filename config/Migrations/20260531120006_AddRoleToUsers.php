<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddRoleToUsers extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('role', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'author',
                'after' => 'name',
            ])
            ->update();
    }
}
