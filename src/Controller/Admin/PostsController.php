<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\ContentTypeFieldSchema;
use App\Model\Entity\Post;
use App\Model\Enum\ContentType;
use App\Model\Enum\PostStatus;
use App\Service\FieldSchema\FieldDataValidator;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;

final class PostsController extends AdminController
{
    public function index(): void
    {
        $posts = $this->fetchTable('Posts');
        $query = $posts->find()
            ->contain(['Authors'])
            ->orderByDesc('Posts.modified');

        $keyword = $this->request->getQuery('q');
        $keyword = is_string($keyword) ? trim($keyword) : '';
        if ($keyword !== '') {
            $query = $posts->findSearch($query, $keyword);
        }

        $status = $this->request->getQuery('status');
        if (is_string($status) && $status !== '') {
            $query = $posts->findByStatus($query, $status);
        }

        $authorId = $this->request->getQuery('author_id');
        if (is_numeric($authorId)) {
            $query = $posts->findByAuthor($query, (int) $authorId);
        }

        $selectedTags = $this->selectedTagFilter();
        $tagSlugs = array_map(static fn (array $tag): string => $tag['slug'], $selectedTags);
        $tagMode = $this->tagFilterMode();

        if ($tagSlugs !== []) {
            $query = $query->find('byTags', slugs: $tagSlugs, matchAll: $tagMode === 'all');
        }

        $this->set([
            'posts' => $this->paginate($query, ['limit' => 20]),
            'authors' => $this->fetchTable('Users')->find('list')->all()->toArray(),
            'statuses' => PostStatus::cases(),
            'keyword' => $keyword,
            'selectedTags' => $selectedTags,
            'tagMode' => $tagMode,
            'activeFilters' => [
                'q' => $keyword !== '' ? $keyword : null,
                'status' => $status,
                'author_id' => $authorId,
                'tags' => $tagSlugs,
            ],
        ]);
    }

    public function add(): ?Response
    {
        $posts = $this->fetchTable('Posts');
        $post = $posts->newEmptyEntity();
        $post->author_id = $this->currentUserId();

        $fieldSchema = $this->fieldSchema();
        $dataErrors = [];
        if ($this->request->is('post')) {
            /** @var array<string, mixed> $data */
            $data = (array) $this->request->getData();
            $data['author_id'] = is_numeric($data['author_id'] ?? null)
                ? (int) $data['author_id']
                : $this->currentUserId();
            $data['status'] ??= PostStatus::Draft->value;

            $validation = new FieldDataValidator()->validate($fieldSchema->fields, $this->dataFrom($data));
            $data['data'] = $validation['data'];
            $post = $posts->patchEntity($post, $data);
            $this->Authorization->authorize($post);

            if ($validation['errors'] === [] && $posts->save($post)) {
                $this->Flash->success('Post created.');

                return $this->redirect(['action' => 'edit', $post->id]);
            }

            $dataErrors = $validation['errors'];
            $this->Flash->error('There were errors. The slug may already be in use.');
        }

        $this->set([
            'post' => $post,
            'statuses' => PostStatus::cases(),
            'authors' => $this->fetchTable('Users')->find()->orderBy(['name' => 'ASC'])->all()->toArray(),
            'fieldSchema' => $fieldSchema,
            'dataErrors' => $dataErrors,
        ]);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $post = $this->postOrFail($id);
        $this->Authorization->authorize($post);

        $fieldSchema = $this->fieldSchema();
        $dataErrors = [];
        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var array<string, mixed> $data */
            $data = (array) $this->request->getData();
            $posts = $this->fetchTable('Posts');

            $validation = new FieldDataValidator()->validate($fieldSchema->fields, $this->dataFrom($data));
            $data['data'] = $validation['data'];
            $post = $posts->patchEntity($post, $data);

            if ($validation['errors'] === [] && $posts->save($post)) {
                $this->Flash->success('Post saved.');

                return $this->redirect(['action' => 'edit', $id]);
            }

            $dataErrors = $validation['errors'];
            $this->Flash->error('Could not save.');
        }

        $this->set([
            'post' => $post,
            'statuses' => PostStatus::cases(),
            'authors' => $this->fetchTable('Users')->find()->orderBy(['name' => 'ASC'])->all()->toArray(),
            'fieldSchema' => $fieldSchema,
            'dataErrors' => $dataErrors,
        ]);

        return null;
    }

    public function delete(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $post = $this->postOrFail($id);
        $this->Authorization->authorize($post);

        $posts = $this->fetchTable('Posts');
        $posts->delete($post)
            ? $this->Flash->success('Post deleted.')
            : $this->Flash->error('Could not delete.');

        return $this->redirect(['action' => 'index']);
    }

    public function saveDraft(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $post = $this->postOrFail($id);
        $this->Authorization->authorize($post);

        $posts = $this->fetchTable('Posts');
        /** @var array<string, mixed> $data */
        $data = (array) $this->request->getData();
        $data['status'] = PostStatus::Draft->value;
        $post = $posts->patchEntity($post, $data);

        $posts->save($post)
            ? $this->Flash->success('Saved as draft.')
            : $this->Flash->error('Could not save.');

        return $this->redirect(['action' => 'edit', $id]);
    }

    public function publish(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $post = $this->postOrFail($id);
        $this->Authorization->authorize($post);

        $posts = $this->fetchTable('Posts');
        /** @var array<string, mixed> $data */
        $data = (array) $this->request->getData();
        $data['status'] = PostStatus::Live->value;
        $data['published_at'] = DateTime::now();
        $post = $posts->patchEntity($post, $data);

        $posts->save($post)
            ? $this->Flash->success('Published.')
            : $this->Flash->error('Could not publish.');

        return $this->redirect(['action' => 'edit', $id]);
    }

    public function schedule(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $post = $this->postOrFail($id);
        $this->Authorization->authorize($post);

        $publishedAt = $this->request->getData('published_at');
        if (!is_string($publishedAt) || $publishedAt === '') {
            $this->Flash->error('Schedule needs a date.');

            return $this->redirect(['action' => 'edit', $id]);
        }

        $posts = $this->fetchTable('Posts');
        /** @var array<string, mixed> $data */
        $data = (array) $this->request->getData();
        $data['status'] = PostStatus::Scheduled->value;
        $data['published_at'] = DateTime::parse($publishedAt);
        $post = $posts->patchEntity($post, $data);

        $posts->save($post)
            ? $this->Flash->success('Scheduled.')
            : $this->Flash->error('Could not schedule.');

        return $this->redirect(['action' => 'edit', $id]);
    }

    public function attachTag(int $id, string $slug): Response
    {
        $this->request->allowMethod('post');
        $this->Authorization->authorize($this->postOrFail($id), 'edit');

        return $this->attachTagTo(ContentType::Posts, $id, $slug);
    }

    public function detachTag(int $id, string $slug): Response
    {
        $this->request->allowMethod('post');
        $this->Authorization->authorize($this->postOrFail($id), 'edit');

        return $this->detachTagFrom(ContentType::Posts, $id, $slug);
    }

    private function postOrFail(int $id): Post
    {
        $post = $this->fetchTable('Posts')->find()
            ->where(['Posts.id' => $id])
            ->contain(['Authors', 'Tags'])
            ->first();

        return $post ?? throw new NotFoundException();
    }

    private function fieldSchema(): ContentTypeFieldSchema
    {
        return $this->fetchTable('ContentTypeFieldSchemas')->schemaFor(ContentType::Posts);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<array-key, mixed>
     */
    private function dataFrom(array $raw): array
    {
        $data = $raw['data'] ?? [];

        return is_array($data) ? $data : [];
    }
}
