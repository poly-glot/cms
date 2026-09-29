<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddPublishedAtToPages extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('pages');
        $table
            ->addColumn('published_at', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'status',
            ])
            ->addIndex(['status', 'published_at'], ['name' => 'idx_pages_status_published_at'])
            ->update();
    }
}
