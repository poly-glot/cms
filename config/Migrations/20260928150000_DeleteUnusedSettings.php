<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class DeleteUnusedSettings extends BaseMigration
{
    public function up(): void
    {
        $this->getDeleteBuilder()
            ->delete('settings')
            ->whereInList('setting_key', ['timezone', 'auto_save_drafts', 'ping_search_engines'])
            ->execute();
    }
}
