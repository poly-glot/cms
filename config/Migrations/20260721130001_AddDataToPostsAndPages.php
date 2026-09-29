<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AddDataToPostsAndPages extends BaseMigration
{
    public function change(): void
    {
        $this->table('posts')
            ->addColumn('data', 'json', ['null' => true, 'default' => null, 'after' => 'body'])
            ->update();

        $this->table('pages')
            ->addColumn('data', 'json', ['null' => true, 'default' => null, 'after' => 'body'])
            ->update();
    }
}
