<?php

declare(strict_types=1);

namespace App\Controller\Admin;

final class DashboardController extends AdminController
{
    use BlockMediaTrait;

    public function index(): void
    {
        $blocks = $this->fetchTable('Blocks')->find('forLibrary')->all();

        $this->set([
            'blocks' => $blocks,
            'mediaById' => $this->blockMediaMap($blocks),
        ]);
    }
}
