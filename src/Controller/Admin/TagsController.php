<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Tag;
use App\Model\Table\TagsTable;
use Cake\Http\Response;

final class TagsController extends AdminController
{
    public function index(): Response
    {
        $tags = $this->fetchTable(TagsTable::class);
        $query = $tags->find()->orderBy(['label' => 'ASC'])->limit(20);

        $q = $this->request->getQuery('q');
        if (is_string($q) && $q !== '') {
            $like = strtolower($q) . '%';
            $query->where(['OR' => ['LOWER(slug) LIKE' => $like, 'LOWER(label) LIKE' => $like]]);
        }

        $payload = $query->all()->map(static fn (Tag $t): array => [
            'id' => $t->id,
            'slug' => $t->slug,
            'label' => $t->label,
        ])->toList();

        return $this->json($payload);
    }
}
