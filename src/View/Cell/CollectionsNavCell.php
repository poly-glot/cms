<?php

declare(strict_types=1);

namespace App\View\Cell;

use Cake\View\Cell;
use Cake\View\View;

/**
 * Renders the per-collection sub-items shown under the "Collections" entry in the admin sidebar.
 *
 * @extends Cell<View>
 */
final class CollectionsNavCell extends Cell
{
    public function display(?int $activeId = null): void
    {
        $collections = $this->fetchTable('Collections')->find()
            ->select(['id', 'name', 'slug'])
            ->orderByAsc('name')
            ->all()
            ->toList();

        $this->set([
            'collections' => $collections,
            'activeId' => $activeId,
        ]);
    }
}
