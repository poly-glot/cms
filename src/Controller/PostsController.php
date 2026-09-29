<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Post;
use Cake\Http\Exception\NotFoundException;
use Override;

final class PostsController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->addUnauthenticatedActions(['index', 'view']);
    }

    public function index(): void
    {
        $query = $this->fetchTable('Posts')
            ->find('live')
            ->contain(['Authors'])
            ->orderByDesc('Posts.published_at')
            ->orderByDesc('Posts.created');

        $this->set('posts', $this->paginate($query, ['limit' => 10]));
    }

    public function view(string $slug): void
    {
        $post = $this->fetchTable('Posts')
            ->find('live')
            ->where(['Posts.slug' => $slug])
            ->contain(['Authors'])
            ->first();

        if (!$post instanceof Post) {
            throw new NotFoundException();
        }

        $this->set('post', $post);
    }
}
