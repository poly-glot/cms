<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddCommentsEnabledToCollectionEntries extends BaseMigration
{
    public function change(): void
    {
        $this->table('collection_entries')
            ->addColumn('comments_enabled', 'boolean', [
                'null' => false,
                'default' => false,
                'after' => 'published_at',
            ])
            ->update();
    }
}
