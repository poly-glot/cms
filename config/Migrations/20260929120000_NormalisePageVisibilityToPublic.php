<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class NormalisePageVisibilityToPublic extends BaseMigration
{
    public function up(): void
    {
        foreach (['pages', 'page_revisions'] as $table) {
            $this->getUpdateBuilder()
                ->update($table)
                ->set('visibility', 'public')
                ->where(['visibility !=' => 'public'])
                ->execute();
        }
    }
}
