<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Exception\NotFoundException;
use Override;

final class PagesController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['view']);
    }

    public function view(string ...$segments): void
    {
        $path = implode('/', $segments);
        if ($path === '') {
            throw new NotFoundException();
        }

        $page = $this->fetchTable('Pages')->findPublicByPath($path);
        if ($page === null) {
            throw new NotFoundException();
        }

        $ancestors = $this->fetchTable('Pages')->ancestorsOf($page->parent_id);

        $template = match ($page->template) {
            'long_form' => 'view_long_form',
            'landing' => 'view_landing',
            default => 'view',
        };

        $comments = $this->fetchTable('Comments')->find('approvedFor', commentableType: 'Pages', commentableId: (int) $page->id)->all();
        $allowComments = $page->comments_enabled && $this->fetchTable('Settings')->isEnabled('allow_comments');

        $this->viewBuilder()->setTemplate($template);
        $this->set(['page' => $page, 'ancestors' => $ancestors, 'comments' => $comments, 'allowComments' => $allowComments]);
    }
}
