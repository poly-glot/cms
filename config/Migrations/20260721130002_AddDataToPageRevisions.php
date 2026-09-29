<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddDataToPageRevisions extends BaseMigration
{
    public function change(): void
    {
        $this->table('page_revisions')
            ->addColumn('data', 'json', ['null' => true, 'default' => null, 'after' => 'body'])
            ->update();
    }
}
