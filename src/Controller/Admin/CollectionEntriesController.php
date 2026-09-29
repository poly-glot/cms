<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Collection;
use App\Model\Entity\CollectionEntry;
use App\Model\Enum\PostStatus;
use App\Service\FieldSchema\FieldDataValidator;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;

final class CollectionEntriesController extends AdminController
{
    public function index(int $collectionId): void
    {
        $collection = $this->collectionOrFail($collectionId);
        $entries = $this->fetchTable('CollectionEntries')
            ->find('forCollection', collectionId: $collectionId)
            ->contain(['Authors'])
            ->orderByDesc('CollectionEntries.modified')
            ->all();

        $this->set([
            'collection' => $collection,
            'entries' => $entries,
        ]);
    }

    public function add(int $collectionId): ?Response
    {
        $collection = $this->collectionOrFail($collectionId);
        $entries = $this->fetchTable('CollectionEntries');
        $entry = $entries->newEmptyEntity();
        $entry->collection_id = $collectionId;
        $this->Authorization->authorize($entry);

        $dataErrors = [];
        if ($this->request->is('post')) {
            /** @var array<string, mixed> $form */
            $form = (array) $this->request->getData();
            $core = $this->coreFields($form);
            $validation = new FieldDataValidator()->validate($collection->fields, $this->dataFrom($form));
            $core['data'] = $validation['data'];
            $core['collection_id'] = $collectionId;

            $entry = $entries->patchEntity($entry, $core);
            $entry->author_id = $this->currentUserId();
            $this->authorizePublishIfRequested($entry);

            if ($validation['errors'] === [] && $entries->save($entry)) {
                $this->Flash->success('Entry created.');

                return $this->redirect(['action' => 'edit', $collectionId, $entry->id]);
            }
            $dataErrors = $validation['errors'];
            $this->Flash->error('Please correct the errors below.');
        }

        $this->set([
            'collection' => $collection,
            'entry' => $entry,
            'statuses' => PostStatus::cases(),
            'dataErrors' => $dataErrors,
        ]);

        return null;
    }

    public function edit(int $collectionId, int $id): ?Response
    {
        $collection = $this->collectionOrFail($collectionId);
        $entry = $this->entryOrFail($collectionId, $id);
        $this->Authorization->authorize($entry);

        $dataErrors = [];
        if ($this->request->is(['patch', 'post', 'put'])) {
            $entries = $this->fetchTable('CollectionEntries');
            /** @var array<string, mixed> $form */
            $form = (array) $this->request->getData();
            $core = $this->coreFields($form);
            $validation = new FieldDataValidator()->validate($collection->fields, $this->dataFrom($form));
            $core['data'] = $validation['data'];

            $entry = $entries->patchEntity($entry, $core);
            $this->authorizePublishIfRequested($entry);

            if ($validation['errors'] === [] && $entries->save($entry)) {
                $this->Flash->success('Entry saved.');

                return $this->redirect(['action' => 'edit', $collectionId, $id]);
            }
            $dataErrors = $validation['errors'];
            $this->Flash->error('Please correct the errors below.');
        }

        $this->set([
            'collection' => $collection,
            'entry' => $entry,
            'statuses' => PostStatus::cases(),
            'dataErrors' => $dataErrors,
        ]);

        return null;
    }

    public function search(): Response
    {
        $slug = $this->request->getQuery('collection');
        $term = $this->request->getQuery('q');
        $term = is_string($term) ? trim($term) : '';

        $collection = is_string($slug) && $slug !== ''
            ? $this->fetchTable('Collections')->find()->where(['Collections.slug' => $slug])->first()
            : null;
        if (!$collection instanceof Collection) {
            return $this->json([]);
        }

        $entries = $this->fetchTable('CollectionEntries')
            ->find('titleSearch', collectionId: (int) $collection->id, term: $term)
            ->all()
            ->map(static fn (CollectionEntry $entry): array => ['id' => $entry->id, 'title' => $entry->title])
            ->toList();

        return $this->json($entries);
    }

    public function resolve(): Response
    {
        $idsParam = $this->request->getQuery('ids');

        $ids = [];
        if (is_string($idsParam)) {
            foreach (explode(',', $idsParam) as $part) {
                if (is_numeric($part) && (int) $part > 0) {
                    $ids[] = (int) $part;
                }
            }
        }
        if ($ids === []) {
            return $this->json([]);
        }

        $entries = $this->fetchTable('CollectionEntries')->find()
            ->where(['CollectionEntries.id IN' => $ids])
            ->all()
            ->map(static fn (CollectionEntry $entry): array => ['id' => $entry->id, 'title' => $entry->title])
            ->toList();

        return $this->json($entries);
    }

    public function delete(int $collectionId, int $id): ?Response
    {
        $this->request->allowMethod('post');
        $entry = $this->entryOrFail($collectionId, $id);
        $this->Authorization->authorize($entry);

        $this->fetchTable('CollectionEntries')->delete($entry)
            ? $this->Flash->success('Entry deleted.')
            : $this->Flash->error('Could not delete.');

        return $this->redirect(['action' => 'index', $collectionId]);
    }

    private function collectionOrFail(int $id): Collection
    {
        $collection = $this->fetchTable('Collections')->find()->where(['id' => $id])->first();

        return $collection ?? throw new NotFoundException();
    }

    private function entryOrFail(int $collectionId, int $id): CollectionEntry
    {
        $entry = $this->fetchTable('CollectionEntries')->find()
            ->where(['CollectionEntries.id' => $id, 'CollectionEntries.collection_id' => $collectionId])
            ->first();

        return $entry ?? throw new NotFoundException();
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function coreFields(array $raw): array
    {
        $core = [
            'title' => is_string($raw['title'] ?? null) ? $raw['title'] : '',
            'slug' => is_string($raw['slug'] ?? null) ? $raw['slug'] : '',
            'status' => is_string($raw['status'] ?? null) ? $raw['status'] : PostStatus::Draft->value,
            'comments_enabled' => !empty($raw['comments_enabled']),
        ];

        if (isset($raw['published_at']) && is_string($raw['published_at']) && $raw['published_at'] !== '') {
            $core['published_at'] = DateTime::parse($raw['published_at']);
        }

        return $core;
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

    private function authorizePublishIfRequested(CollectionEntry $entry): void
    {
        $publishing = in_array($entry->status, [PostStatus::Live->value, PostStatus::Scheduled->value], true);
        if ($publishing && $entry->isDirty('status')) {
            $this->Authorization->authorize($entry, 'publish');
        }
    }
}
