<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSettings extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('settings', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('setting_key', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('value', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['setting_key'], ['unique' => true, 'name' => 'uq_settings_key'])
            ->create();
    }
}
