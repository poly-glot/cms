<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddStatusScheduledCheckToPages extends BaseMigration
{
    public function up(): void
    {
        $this->execute("ALTER TABLE pages ADD CONSTRAINT pages_status_chk_1 CHECK (status IN ('draft', 'live', 'scheduled'))");
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE pages DROP CHECK pages_status_chk_1');
    }
}
