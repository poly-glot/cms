<?php

declare(strict_types=1);

namespace App\View\Cell;

use Cake\View\Cell;
use Cake\View\View;

/**
 * Renders the unread-comment count badge for the admin sidebar.
 *
 * @extends Cell<View>
 */
final class CommentsBadgeCell extends Cell
{
    public function display(): void
    {
        $this->set('count', $this->fetchTable('Comments')->unreadCount());
    }
}
