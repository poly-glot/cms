<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class ReplaceSlugUniqueWithComposite extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('pages');
        $table
            ->removeIndex(['slug'])
            ->update();

        $this->execute(
            'ALTER TABLE pages ADD CONSTRAINT uq_pages_slug_parent UNIQUE (slug, parent_id)'
        );
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE pages DROP INDEX uq_pages_slug_parent');
        $table = $this->table('pages');
        $table
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_pages_slug'])
            ->update();
    }
}
