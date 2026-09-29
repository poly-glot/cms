<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddVisibilityToPages extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('pages');
        $table
            ->addColumn('visibility', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'public',
                'after' => 'template',
            ])
            ->update();

        $this->execute("ALTER TABLE pages ADD CONSTRAINT pages_visibility_chk CHECK (visibility IN ('public', 'private', 'unlisted'))");
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE pages DROP CHECK pages_visibility_chk');
        $table = $this->table('pages');
        $table->removeColumn('visibility')->update();
    }
}
