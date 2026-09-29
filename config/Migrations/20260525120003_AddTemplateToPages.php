<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddTemplateToPages extends BaseMigration
{
    public function up(): void
    {
        $table = $this->table('pages');
        $table
            ->addColumn('template', 'string', [
                'limit' => 32,
                'null' => false,
                'default' => 'default',
                'after' => 'published_at',
            ])
            ->update();

        $this->execute("ALTER TABLE pages ADD CONSTRAINT pages_template_chk CHECK (template IN ('default', 'long_form', 'landing'))");
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE pages DROP CHECK pages_template_chk');
        $table = $this->table('pages');
        $table->removeColumn('template')->update();
    }
}
