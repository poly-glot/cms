<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Collection;
use App\Model\Enum\CollectionFieldType;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

final class CollectionsController extends AdminController
{
    public function index(): void
    {
        $this->set([
            'collections' => $this->fetchTable('Collections')->find()->orderByAsc('Collections.name')->all(),
        ]);
    }

    public function add(): ?Response
    {
        $collections = $this->fetchTable('Collections');
        $collection = $collections->newEmptyEntity();
        $this->Authorization->authorize($collection);

        if ($this->request->is('post')) {
            /** @var array<string, mixed> $form */
            $form = (array) $this->request->getData();
            $data = $this->parseForm($form);
            $collection = $collections->patchEntity($collection, $data);

            if ($collections->save($collection)) {
                $this->Flash->success('Collection created.');

                return $this->redirect(['action' => 'edit', $collection->id]);
            }
            $this->Flash->error('Please correct the errors below.');
        }

        $this->set([
            'collection' => $collection,
            'fieldTypes' => CollectionFieldType::cases(),
            'fieldsInUse' => [],
        ]);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $collections = $this->fetchTable('Collections');
        $collection = $this->collectionOrFail($id);
        $this->Authorization->authorize($collection);

        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var array<string, mixed> $form */
            $form = (array) $this->request->getData();
            $data = $this->parseForm($form);
            $collection = $collections->patchEntity($collection, $data);

            if ($collections->save($collection)) {
                $this->Flash->success('Collection saved.');

                return $this->redirect(['action' => 'edit', $id]);
            }
            $this->Flash->error('Please correct the errors below.');
        }

        $this->set([
            'collection' => $collection,
            'fieldTypes' => CollectionFieldType::cases(),
            'fieldsInUse' => $this->fetchTable('CollectionEntries')->getBehavior('EditorialContent')->dataUsageByField('forCollection', collectionId: $id),
        ]);

        return null;
    }

    public function list(): Response
    {
        $payload = $this->fetchTable('Collections')
            ->find('forReferencePicker')
            ->all()
            ->map(static fn (Collection $collection): array => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
            ])
            ->toList();

        return $this->json($payload);
    }

    public function delete(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $collection = $this->collectionOrFail($id);
        $this->Authorization->authorize($collection);

        $this->fetchTable('Collections')->delete($collection)
            ? $this->Flash->success('Collection deleted.')
            : $this->Flash->error('Could not delete.');

        return $this->redirect(['action' => 'index']);
    }

    private function collectionOrFail(int $id): Collection
    {
        $collection = $this->fetchTable('Collections')->find()
            ->where(['Collections.id' => $id])
            ->first();

        return $collection ?? throw new NotFoundException();
    }

    /**
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function parseForm(array $raw): array
    {
        $raw['field_schema'] = $this->decodeFieldSchema($raw);
        unset($raw['field_schema_json']);

        return $raw;
    }
}
