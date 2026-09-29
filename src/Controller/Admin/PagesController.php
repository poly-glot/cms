<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\ContentTypeFieldSchema;
use App\Model\Entity\Page;
use App\Model\Enum\ContentType;
use App\Model\Enum\PostStatus;
use App\Model\Tenancy\TenantContext;
use App\Service\FieldSchema\FieldDataValidator;
use App\Service\Page\PagePublisher;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Exception\PersistenceFailedException;
use Closure;
use DomainException;

final class PagesController extends AdminController
{
    public function index(): void
    {
        $pages = $this->fetchTable('Pages');

        $status = $this->request->getQuery('status');
        $authorId = $this->request->getQuery('author_id');
        $parentId = $this->request->getQuery('parent_id');

        $selectedTags = $this->selectedTagFilter();
        $tagSlugs = array_map(static fn (array $tag): string => $tag['slug'], $selectedTags);
        $tagMode = $this->tagFilterMode();

        $filters = [
            'status' => is_string($status) && $status !== '' ? $status : null,
            'author_id' => is_numeric($authorId) ? (string) (int) $authorId : null,
            'tags' => $tagSlugs,
            'parent_id' => is_numeric($parentId) ? (string) (int) $parentId : null,
        ];
        $hasFilters = array_filter($filters) !== [];

        if ($hasFilters) {
            $query = $pages->find()->contain(['Authors', 'Tags'])->orderByDesc('Pages.modified');

            if ($filters['status'] !== null) {
                $query = $pages->findByStatus($query, $filters['status']);
            }

            if ($filters['author_id'] !== null) {
                $query = $pages->findByAuthor($query, (int) $filters['author_id']);
            }

            if ($tagSlugs !== []) {
                $query = $query->find('byTags', slugs: $tagSlugs, matchAll: $tagMode === 'all');
            }

            if ($filters['parent_id'] !== null) {
                $query = $pages->findByParent($query, (int) $filters['parent_id']);
            }

            $tree = $query->all()->toList();
        } else {
            $tree = $pages->find('threaded')
                ->contain(['Authors'])
                ->orderBy(['Pages.position' => 'ASC', 'Pages.title' => 'ASC'])
                ->all()
                ->toList();
        }

        $this->set([
            'tree' => $tree,
            'filtered' => $hasFilters,
            'authors' => $this->fetchTable('Users')->find('list')->all()->toArray(),
            'parentOptions' => $this->buildPageTreeList(),
            'selectedTags' => $selectedTags,
            'tagMode' => $tagMode,
            'activeFilters' => $filters,
        ]);
    }

    public function reorder(): Response
    {
        $this->request->allowMethod('post');

        $order = $this->request->getData('order');
        if (is_string($order)) {
            $order = json_decode($order, true);
        }
        if (!is_array($order)) {
            throw new BadRequestException('Invalid order payload.');
        }

        $rows = [];
        foreach ($order as $node) {
            if (!is_array($node) || !isset($node['id']) || !is_numeric($node['id'])) {
                continue;
            }

            $rows[] = [
                'id' => (int) $node['id'],
                'parentId' => isset($node['parentId']) && is_numeric($node['parentId']) ? (int) $node['parentId'] : null,
                'position' => isset($node['position']) && is_numeric($node['position']) ? (int) $node['position'] : count($rows),
            ];
        }

        $parentOf = [];
        foreach ($rows as $row) {
            $parentOf[$row['id']] = $row['parentId'];
        }

        $this->guardNoCycles($parentOf);

        $pages = $this->fetchTable('Pages');
        $workspaceId = TenantContext::instance()->requireWorkspaceId();

        $pages->getConnection()->transactional(static function () use ($pages, $rows, $workspaceId): void {
            foreach ($rows as $row) {
                $pages->updateAll(
                    ['parent_id' => $row['parentId'], 'position' => $row['position']],
                    ['Pages.id' => $row['id'], 'Pages.workspace_id' => $workspaceId],
                );
            }
        });

        return $this->json(['ok' => true]);
    }

    public function bulkStatus(): ?Response
    {
        $this->request->allowMethod('post');

        $ids = array_values(array_filter(array_map(
            static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0,
            (array) $this->request->getData('ids', []),
        )));
        $status = $this->request->getData('status');
        $allowed = [PostStatus::Draft->value, PostStatus::Live->value];

        if ($ids === [] || !is_string($status) || !in_array($status, $allowed, true)) {
            $this->Flash->error('Select one or more pages and a valid status.');

            return $this->redirect(['action' => 'index']);
        }

        $workspaceId = TenantContext::instance()->requireWorkspaceId();
        $updated = $this->fetchTable('Pages')->updateAll(
            ['status' => $status],
            ['Pages.id IN' => $ids, 'Pages.workspace_id' => $workspaceId],
        );

        $this->Flash->success(sprintf('%d page(s) set to %s.', $updated, $status === PostStatus::Live->value ? 'published' : 'draft'));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @param array<int, int|null> $parentOf
     */
    private function guardNoCycles(array $parentOf): void
    {
        foreach (array_keys($parentOf) as $id) {
            $seen = [];
            $cursor = $parentOf[$id] ?? null;

            while ($cursor !== null) {
                if ($cursor === $id || isset($seen[$cursor])) {
                    throw new BadRequestException('Reorder would create a circular hierarchy.');
                }
                $seen[$cursor] = true;
                $cursor = $parentOf[$cursor] ?? null;
            }
        }
    }

    public function add(): ?Response
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->newEmptyEntity();

        $fieldSchema = $this->fieldSchema();
        $dataErrors = [];
        if ($this->request->is('post')) {
            /** @var array<string, mixed> $data */
            $data = (array) $this->request->getData();
            $data['author_id'] = $this->currentUserId();

            $validation = new FieldDataValidator()->validate($fieldSchema->fields, $this->dataFrom($data));
            $data['data'] = $validation['data'];
            $page = $pages->patchEntity($page, $data);
            $this->Authorization->authorize($page);

            if ($validation['errors'] === [] && $pages->save($page)) {
                $this->Flash->success('Page created.');

                return $this->redirect(['action' => 'edit', $page->id]);
            }

            $dataErrors = $validation['errors'];
            $this->Flash->error('There were errors. The slug may already exist within this parent.');
        }

        $treeList = $this->buildPageTreeList();
        $authors = $this->fetchTable('Users')->find()->orderBy(['name' => 'ASC'])->all()->toArray();
        $revisions = [];

        $this->set(['page' => $page, 'treeList' => $treeList, 'authors' => $authors, 'revisions' => $revisions, 'fieldSchema' => $fieldSchema, 'dataErrors' => $dataErrors]);

        return null;
    }

    public function edit(int $id): ?Response
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->find()
            ->where(['Pages.id' => $id])
            ->contain(['Authors', 'Tags', 'PageRevisions' => ['Authors'], 'ParentPages'])
            ->first()
            ?? throw new NotFoundException();
        $this->Authorization->authorize($page);

        $fieldSchema = $this->fieldSchema();
        $dataErrors = [];
        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var array<string, mixed> $data */
            $data = (array) $this->request->getData();

            $validation = new FieldDataValidator()->validate($fieldSchema->fields, $this->dataFrom($data));
            $data['data'] = $validation['data'];
            $page = $pages->patchEntity($page, $data);

            if ($validation['errors'] === [] && $pages->save($page)) {
                $this->Flash->success('Page saved.');

                return $this->redirect(['action' => 'edit', $id]);
            }

            $dataErrors = $validation['errors'];
            $this->Flash->error('Could not save.');
        }

        $treeList = $this->buildPageTreeList(excludeId: $id);
        $authors = $this->fetchTable('Users')->find()->orderBy(['name' => 'ASC'])->all()->toArray();
        $revisions = $page->page_revisions ?? [];
        usort($revisions, static fn ($a, $b): int => $b->version <=> $a->version);
        $parentPath = $page->parent_id === null ? null : ($pages->pathsById()[$page->parent_id] ?? null);

        $this->set(['page' => $page, 'treeList' => $treeList, 'authors' => $authors, 'revisions' => $revisions, 'fieldSchema' => $fieldSchema, 'dataErrors' => $dataErrors, 'parentPath' => $parentPath]);

        return null;
    }

    public function delete(int $id): ?Response
    {
        $this->request->allowMethod('post');
        $pages = $this->fetchTable('Pages');
        $page = $pages->find()->where(['id' => $id])->first()
            ?? throw new NotFoundException();
        $this->Authorization->authorize($page);

        if ($pages->delete($page)) {
            $this->Flash->success('Page deleted.');
        } else {
            $this->Flash->error('Could not delete.');
        }

        return $this->redirect(['action' => 'index']);
    }

    public function saveDraft(int $id): ?Response
    {
        $page = $this->pageOrFail($id);

        return $this->transition(
            $id,
            'Saved as draft.',
            'Could not save: ',
            fn (array $data): Page => new PagePublisher()->saveDraft($page, $data, authorId: $this->currentUserId()),
        );
    }

    public function publish(int $id): ?Response
    {
        $page = $this->pageOrFail($id);

        return $this->transition(
            $id,
            'Published.',
            'Could not publish: ',
            fn (array $data): Page => new PagePublisher()->publish($page, $data, authorId: $this->currentUserId()),
        );
    }

    public function schedule(int $id): ?Response
    {
        $page = $this->pageOrFail($id);

        $publishedAt = $this->request->getData('published_at');
        if (!is_string($publishedAt) || $publishedAt === '') {
            $this->Flash->error('Schedule needs a date.');

            return $this->redirect(['action' => 'edit', $id]);
        }

        return $this->transition(
            $id,
            'Scheduled.',
            'Could not schedule: ',
            fn (array $data): Page => new PagePublisher()->schedule($page, $data, when: DateTime::parse($publishedAt), authorId: $this->currentUserId()),
        );
    }

    /**
     * @param Closure(array<string, mixed>): Page $apply
     */
    private function transition(int $id, string $success, string $failurePrefix, Closure $apply): ?Response
    {
        /** @var array<string, mixed> $data */
        $data = (array) $this->request->getData();
        $validation = $this->coerceData($data);
        if ($validation['errors'] !== []) {
            $this->Flash->error('Please correct the custom fields.');

            return $this->redirect(['action' => 'edit', $id]);
        }
        $data['data'] = $validation['data'];

        try {
            $apply($data);
            $this->Flash->success($success);
        } catch (DomainException $e) {
            $this->Flash->error($e->getMessage());
        } catch (PersistenceFailedException $e) {
            $this->Flash->error($failurePrefix . $e->getMessage());
        }

        return $this->redirect(['action' => 'edit', $id]);
    }

    public function autosave(int $id): Response
    {
        $pages = $this->fetchTable('Pages');
        $page = $pages->find()->where(['id' => $id])->first();
        if ($page === null) {
            throw new NotFoundException();
        }
        $this->Authorization->authorize($page, 'edit');

        $page = $pages->patchEntity($page, (array) $this->request->getData(), [
            'validate' => false,
            'fields' => ['title', 'slug', 'body', 'data', 'status', 'author_id', 'template', 'parent_id', 'position', 'comments_enabled'],
        ]);
        $pages->saveOrFail($page, ['checkRules' => false]);

        return $this->json([
            'savedAt' => $page->modified->format(\DATE_ATOM),
            'modifiedTimestamp' => $page->modified->getTimestamp(),
        ]);
    }

    public function restore(int $id, int $version): ?Response
    {
        $this->request->allowMethod('post');
        $page = $this->pageOrFail($id);

        $authorId = $this->currentUserId();

        new PagePublisher()->restore(pageId: $id, version: $version, authorId: $authorId);
        $this->Flash->success(sprintf('Restored from v%d.', $version));

        return $this->redirect(['action' => 'edit', $id]);
    }

    public function attachTag(int $id, string $slug): Response
    {
        $this->request->allowMethod('post');
        $this->pageOrFail($id);

        return $this->attachTagTo(ContentType::Pages, $id, $slug);
    }

    public function detachTag(int $id, string $slug): Response
    {
        $this->request->allowMethod('post');
        $this->pageOrFail($id);

        return $this->detachTagFrom(ContentType::Pages, $id, $slug);
    }

    /**
     * @return array<int, string>
     */
    private function buildPageTreeList(int $excludeId = 0): array
    {
        $pages = $this->fetchTable('Pages')
            ->find()
            ->orderBy(['Pages.position' => 'ASC', 'Pages.title' => 'ASC'])
            ->all()
            ->toList();

        $byParent = [];
        foreach ($pages as $page) {
            if ($page->id === $excludeId) {
                continue;
            }
            $byParent[$page->parent_id ?? 0][] = $page;
        }

        $result = [];
        $walk = static function (int $parentId, int $depth) use (&$walk, &$byParent, &$result): void {
            foreach ($byParent[$parentId] ?? [] as $page) {
                $result[$page->id] = str_repeat('— ', $depth) . $page->title;
                $walk($page->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $result;
    }

    private function pageOrFail(int $id): Page
    {
        $page = $this->fetchTable('Pages')->find()
            ->where(['id' => $id])
            ->contain(['Tags'])
            ->first();
        if ($page === null) {
            throw new NotFoundException();
        }
        $this->Authorization->authorize($page);

        return $page;
    }

    private function fieldSchema(): ContentTypeFieldSchema
    {
        return $this->fetchTable('ContentTypeFieldSchemas')->schemaFor(ContentType::Pages);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{data: array<string, mixed>, errors: array<string, string>}
     */
    private function coerceData(array $data): array
    {
        return new FieldDataValidator()->validate($this->fieldSchema()->fields, $this->dataFrom($data));
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
