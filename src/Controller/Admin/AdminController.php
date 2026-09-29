<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Model\Entity\Tag;
use App\Model\Enum\ContentType;
use Authorization\Controller\Component\AuthorizationComponent;
use Cake\Http\Response;
use Override;

/**
 * @property AuthorizationComponent $Authorization
 */
abstract class AdminController extends AppController
{
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * @return list<array{slug: string, label: string}>
     */
    protected function selectedTagFilter(): array
    {
        $requested = $this->request->getQuery('tags');
        $requested = is_array($requested) ? $requested : [];

        $slugs = array_values(array_unique(array_filter(
            $requested,
            static fn (mixed $slug): bool => is_string($slug) && $slug !== '',
        )));

        if ($slugs === []) {
            return [];
        }

        $tags = $this->fetchTable('Tags')->find()
            ->where(['Tags.slug IN' => $slugs])
            ->orderBy(['Tags.label' => 'ASC'])
            ->all()
            ->toList();

        return array_values(array_map(
            static fn (Tag $tag): array => ['slug' => $tag->slug, 'label' => $tag->label],
            $tags,
        ));
    }

    protected function tagFilterMode(): string
    {
        return $this->request->getQuery('tag_mode') === 'any' ? 'any' : 'all';
    }

    /**
     * @param array<array-key, mixed> $data
     */
    protected function json(array $data, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string) json_encode($data));
    }

    protected function attachTagTo(ContentType $type, int $id, string $slug): Response
    {
        $tag = $this->fetchTable('Tags')->findOrCreateBySlug($slug);
        $joins = $this->fetchTable('Taggables');

        $key = ['taggable_type' => $type->value, 'taggable_id' => $id, 'tag_id' => $tag->id];
        if ($joins->find()->where($key)->first() === null) {
            $joins->save($joins->newEntity($key));
        }

        return $this->json([
            'id' => $tag->id,
            'slug' => $tag->slug,
            'label' => $tag->label,
        ]);
    }

    protected function detachTagFrom(ContentType $type, int $id, string $slug): Response
    {
        $tag = $this->fetchTable('Tags')->find()->where(['slug' => $slug])->first();
        if ($tag === null) {
            return $this->json(['ok' => true]);
        }

        $joins = $this->fetchTable('Taggables');
        $join = $joins->find()->where(['taggable_type' => $type->value, 'taggable_id' => $id, 'tag_id' => $tag->id])->first();
        if ($join !== null) {
            $joins->delete($join);
        }

        return $this->json(['ok' => true]);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<array-key, mixed>
     */
    protected function decodeFieldSchema(array $raw): array
    {
        $json = $raw['field_schema_json'] ?? null;
        $decoded = is_string($json) && $json !== '' ? json_decode($json, true) : [];

        return is_array($decoded) ? $decoded : [];
    }

    protected function currentUserId(): int
    {
        $id = $this->Authentication->getIdentityData('id');

        return is_numeric($id) ? (int) $id : 0;
    }
}
