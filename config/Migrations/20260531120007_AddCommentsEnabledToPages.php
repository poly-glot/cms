<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddCommentsEnabledToPages extends BaseMigration
{
    public function change(): void
    {
        $this->table('pages')
            ->addColumn('comments_enabled', 'boolean', [
                'null' => false,
                'default' => true,
                'after' => 'visibility',
            ])
            ->update();
    }
}
