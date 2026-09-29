<?php

declare(strict_types=1);

use Migrations\BaseSeed;

final class InitialSeed extends BaseSeed
{
    public function run(): void
    {
        $hash = password_hash(getenv('CABINET_SEED_PASSWORD') ?: 'dev-password-1', \PASSWORD_BCRYPT);
        $now = date('Y-m-d H:i:s');

        $this->table('users')->insert([
            [
                'id' => 1,
                'email' => 'admin@cabinet.local',
                'password' => $hash,
                'name' => 'Admin',
                'created' => $now,
                'modified' => $now,
            ],
            ['id' => 2, 'email' => 'eleanor@cabinet.local', 'password' => $hash, 'name' => 'Eleanor Voss', 'created' => '2017-03-01 10:00:00', 'modified' => $now],
            ['id' => 3, 'email' => 'marcus@cabinet.local', 'password' => $hash, 'name' => 'Marcus Hale', 'created' => '2021-06-01 10:00:00', 'modified' => $now],
            ['id' => 4, 'email' => 'jun@cabinet.local', 'password' => $hash, 'name' => 'Jun Park', 'created' => '2024-09-01 10:00:00', 'modified' => $now],
        ])->saveData();

        $this->table('memberships')->insert([
            ['workspace_id' => 1, 'user_id' => 1, 'role' => 'admin', 'created' => $now, 'modified' => $now],
            ['workspace_id' => 1, 'user_id' => 2, 'role' => 'editor', 'created' => $now, 'modified' => $now],
            ['workspace_id' => 1, 'user_id' => 3, 'role' => 'author', 'created' => $now, 'modified' => $now],
            ['workspace_id' => 1, 'user_id' => 4, 'role' => 'contributor', 'created' => $now, 'modified' => $now],
        ])->saveData();

        $this->table('pages')->insert([
            [
                'id' => 1,
                'workspace_id' => 1,
                'title' => 'About Us',
                'slug' => 'about',
                'body' => '<p>Made by hand, since 1992.</p><div data-block="1"></div>',
                'status' => 'live',
                'published_at' => null,
                'template' => 'default',
                'visibility' => 'public',
                'parent_id' => null,
                'position' => 0,
                'author_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 2,
                'workspace_id' => 1,
                'title' => 'Press',
                'slug' => 'press',
                'body' => '<p>Press materials available on request.</p>',
                'status' => 'live',
                'published_at' => null,
                'template' => 'default',
                'visibility' => 'public',
                'parent_id' => 1,
                'position' => 0,
                'author_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 3,
                'workspace_id' => 1,
                'title' => 'Draft Page',
                'slug' => 'draft-page',
                'body' => '<p>Not yet published.</p>',
                'status' => 'draft',
                'published_at' => null,
                'template' => 'default',
                'visibility' => 'public',
                'parent_id' => null,
                'position' => 1,
                'author_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();

        $this->table('tags')->insert([
            ['id' => 1, 'workspace_id' => 1, 'slug' => 'heritage', 'label' => 'heritage', 'created' => $now],
            ['id' => 2, 'workspace_id' => 1, 'slug' => 'company', 'label' => 'company', 'created' => $now],
        ])->saveData();

        $this->table('taggables')->insert([
            ['taggable_id' => 1, 'taggable_type' => 'Pages', 'tag_id' => 1, 'workspace_id' => 1],
            ['taggable_id' => 1, 'taggable_type' => 'Pages', 'tag_id' => 2, 'workspace_id' => 1],
        ])->saveData();

        $this->table('page_revisions')->insert([
            [
                'workspace_id' => 1,
                'page_id' => 1,
                'version' => 1,
                'author_id' => 1,
                'title' => 'About Us',
                'slug' => 'about',
                'body' => '<p>Made by hand, since 1992.</p>',
                'status' => 'live',
                'template' => 'default',
                'published_at' => null,
                'parent_id' => null,
                'visibility' => 'public',
                'note' => 'Initial seed',
                'created' => $now,
            ],
        ])->saveData();

        $this->table('blocks')->insert([
            [
                'id' => 1,
                'workspace_id' => 1,
                'block_type' => 'callout',
                'name' => 'Opening hours',
                'data' => '{"variant":"info","body":"Open Monday to Friday, 9am to 5pm."}',
                'author_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 2,
                'workspace_id' => 1,
                'block_type' => 'quote',
                'name' => 'Founder quote',
                'data' => '{"text":"Made by hand, since 1992.","attribution":"The Cabinet Makers"}',
                'author_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 3,
                'workspace_id' => 1,
                'block_type' => 'statistic',
                'name' => '32 Years Strong',
                'data' => '{"value":"32","label":"Years on the same workbench","caption":"Bleecker Street, est. 1992"}',
                'author_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 4,
                'workspace_id' => 1,
                'block_type' => 'cta',
                'name' => 'Repair Promise',
                'data' => '{"label":"Read the promise","url":"/about","style":"primary"}',
                'author_id' => 1,
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();

        $this->table('page_blocks')->insert([
            ['page_id' => 1, 'block_id' => 1, 'workspace_id' => 1],
        ])->saveData();

        $this->table('comments')->insert([
            [
                'id' => 1,
                'workspace_id' => 1,
                'commentable_type' => 'Pages',
                'commentable_id' => 1,
                'parent_id' => null,
                'author_name' => 'Marcus Hale',
                'author_email' => 'marcus@example.com',
                'body' => "Loved the photo of the workbench. Any chance you'd consider shipping to the UK? I'd buy a bench lamp tomorrow.",
                'status' => 'pending',
                'is_read' => false,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 2,
                'workspace_id' => 1,
                'commentable_type' => 'Pages',
                'commentable_id' => 1,
                'parent_id' => null,
                'author_name' => 'Saoirse Lin',
                'author_email' => null,
                'body' => 'The lifetime repair promise is wonderful — is it transferable if the piece is sold or gifted on?',
                'status' => 'pending',
                'is_read' => false,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 3,
                'workspace_id' => 1,
                'commentable_type' => 'Pages',
                'commentable_id' => 2,
                'parent_id' => null,
                'author_name' => 'Jun Park',
                'author_email' => 'jun@example.com',
                'body' => 'Mine arrived this morning. Beautifully packed — and the joinery is exactly as you described it. Thank you.',
                'status' => 'approved',
                'is_read' => true,
                'created' => $now,
                'modified' => $now,
            ],
            [
                'id' => 4,
                'workspace_id' => 1,
                'commentable_type' => 'Pages',
                'commentable_id' => 2,
                'parent_id' => 3,
                'author_name' => 'A. Cabinet',
                'author_email' => null,
                'body' => 'Thank you, Jun — that means a great deal. Enjoy the stool!',
                'status' => 'approved',
                'is_read' => true,
                'created' => $now,
                'modified' => $now,
            ],
        ])->saveData();

        $this->table('menus')->insert([
            ['id' => 1, 'workspace_id' => 1, 'name' => 'Main Menu', 'slug' => 'main', 'created' => $now, 'modified' => $now],
            ['id' => 2, 'workspace_id' => 1, 'name' => 'Footer', 'slug' => 'footer', 'created' => $now, 'modified' => $now],
            ['id' => 3, 'workspace_id' => 1, 'name' => 'Account', 'slug' => 'account', 'created' => $now, 'modified' => $now],
        ])->saveData();

        $item = static fn (int $id, int $menuId, ?int $parentId, int $position, string $title, string $type, ?int $pageId, ?string $url, string $target): array => [
            'id' => $id,
            'workspace_id' => 1,
            'menu_id' => $menuId,
            'parent_id' => $parentId,
            'position' => $position,
            'title' => $title,
            'type' => $type,
            'page_id' => $pageId,
            'url' => $url,
            'target' => $target,
            'created' => $now,
            'modified' => $now,
        ];
        $this->table('menu_items')->insert([
            $item(1, 1, null, 0, 'Home', 'url', null, '/', '_self'),
            $item(2, 1, null, 1, 'About', 'page', 1, null, '_self'),
            $item(3, 1, 2, 0, 'Press', 'page', 2, null, '_self'),
            $item(4, 1, null, 2, 'Shop', 'url', null, 'https://shop.example.com', '_blank'),
            $item(5, 1, null, 3, 'Contact', 'url', null, '/contact', '_self'),
            $item(6, 2, null, 0, 'About', 'page', 1, null, '_self'),
            $item(7, 2, null, 1, 'Press', 'page', 2, null, '_self'),
            $item(8, 2, null, 2, 'Instagram', 'url', null, 'https://instagram.com/cabinetco', '_blank'),
            $item(9, 3, null, 0, 'My Account', 'url', null, '/about', '_self'),
            $item(10, 3, null, 1, 'Sign out', 'url', null, '/logout', '_self'),
        ])->saveData();
    }
}
