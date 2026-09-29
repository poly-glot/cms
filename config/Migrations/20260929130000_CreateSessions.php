<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSessions extends BaseMigration
{
    public function change(): void
    {
        $this->table('sessions', ['id' => false, 'primary_key' => 'id'])
            ->addColumn('id', 'string', ['limit' => 128, 'null' => false])
            ->addColumn('data', 'blob', ['null' => true])
            ->addColumn('expires', 'integer', ['null' => true])
            ->addIndex('expires')
            ->create();
    }
}
