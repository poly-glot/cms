<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\Entity\Collection;
use App\Model\Entity\CollectionEntry;
use App\Model\Entity\Page;
use App\Model\Entity\Post;
use App\Model\Entity\Workspace;
use App\Model\Enum\CollectionFieldType;
use App\Model\Enum\ContentType;
use App\Service\Collection\TagSyncer;
use App\Service\FieldSchema\FieldDataValidator;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Closure;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class ContentImporter
{
    use LocatorAwareTrait;

    private const string SCHEMA_SUBJECT = 'Collections';

    /**
     * @param list<ContentKind> $kinds
     * @return list<ContentImportResult>
     */
    public function import(string $root, string $workspaceSlug, array $kinds, string $collectionSlug = '', bool $force = false): array
    {
        return $this->fetchTable('Workspaces')->runAsAdmin($workspaceSlug, function (Workspace $workspace) use ($root, $kinds, $collectionSlug, $force): array {
            $base = rtrim($root, '/') . '/' . $workspace->slug;

            $results = [];
            foreach ($kinds as $kind) {
                $results = [...$results, ...match ($kind) {
                    ContentKind::Posts => $this->importDirectory('Posts', $base . '/posts', $force, $this->buildPost(...)),
                    ContentKind::Pages => $this->importDirectory('Pages', $base . '/pages', $force, $this->buildPage(...), $this->pageIdentifier(...)),
                    ContentKind::Entries => $this->importEntries($base . '/collections', $collectionSlug, $force),
                }];
            }

            return $results;
        });
    }

    /**
     * @return list<ContentImportResult>
     */
    public function importSchemas(string $directory, string $workspaceSlug, bool $force = false): array
    {
        return $this->fetchTable('Workspaces')->runAsAdmin(
            $workspaceSlug,
            fn (): array => $this->importDirectory(self::SCHEMA_SUBJECT, $directory, $force, $this->buildCollection(...)),
        );
    }

    /**
     * @return list<ContentImportResult>
     */
    private function importEntries(string $directory, string $collectionSlug, bool $force): array
    {
        $results = [];
        foreach ($this->fetchTable('Collections')->find('forSync', slug: $collectionSlug) as $collection) {
            $results = [...$results, ...$this->importDirectory(
                'CollectionEntries:' . $collection->slug,
                $directory . '/' . $collection->slug,
                $force,
                fn (array $document, string $slug): EntityInterface|string => $this->buildEntry($collection, $document, $slug),
            )];
        }

        return $results;
    }

    /**
     * @param Closure(array<array-key, mixed>, string): (EntityInterface|string) $build
     * @param (Closure(array<array-key, mixed>, string): string)|null $identify
     * @return list<ContentImportResult>
     */
    private function importDirectory(string $subjectType, string $directory, bool $force, Closure $build, ?Closure $identify = null): array
    {
        return array_map(
            fn (string $file): ContentImportResult => $this->importDocument($subjectType, $file, $force, $build, $identify),
            $this->yamlFiles($directory),
        );
    }

    /**
     * @param Closure(array<array-key, mixed>, string): (EntityInterface|string) $build
     * @param (Closure(array<array-key, mixed>, string): string)|null $identify
     */
    private function importDocument(string $subjectType, string $file, bool $force, Closure $build, ?Closure $identify): ContentImportResult
    {
        $document = $this->readDocument($file);
        if (is_string($document)) {
            return $this->failed($subjectType, basename($file, '.yml'), $document);
        }

        $slug = $this->stringValue($document['parsed']['slug'] ?? null);
        if ($slug === '') {
            return $this->failed($subjectType, basename($file, '.yml'), 'Missing "slug".');
        }

        $identifier = $identify === null ? $slug : $identify($document['parsed'], $slug);
        $imports = $this->fetchTable('ContentImports');
        if (!$force && $imports->hashFor($subjectType, $identifier) === $document['hash']) {
            return new ContentImportResult($subjectType, $identifier, ContentImportOutcome::Skipped);
        }

        $entity = $build($document['parsed'], $slug);
        if (is_string($entity)) {
            return $this->failed($subjectType, $identifier, $entity);
        }

        $isNew = $entity->isNew();
        if (!$this->fetchTable($entity->getSource())->save($entity)) {
            return $this->failed($subjectType, $identifier, $this->firstError($entity));
        }

        if ($entity instanceof Post || $entity instanceof Page) {
            $tags = $document['parsed']['tags'] ?? null;
            new TagSyncer()->replace($subjectType, $entity->id, is_array($tags) ? $tags : []);
        }

        $imports->record($subjectType, $identifier, $document['hash']);

        return new ContentImportResult($subjectType, $identifier, $isNew ? ContentImportOutcome::Created : ContentImportOutcome::Updated);
    }

    /**
     * @param array<array-key, mixed> $document
     */
    private function buildCollection(array $document, string $slug): Collection
    {
        $collections = $this->fetchTable('Collections');
        $existing = $collections->findBySlug($slug)->first();
        $collection = $existing instanceof Collection ? $existing : $collections->newEmptyEntity();

        return $collections->patchEntity($collection, [
            'name' => $document['name'] ?? null,
            'slug' => $slug,
            'description' => $document['description'] ?? null,
            'field_schema' => $document['fields'] ?? [],
        ]);
    }

    /**
     * @param array<array-key, mixed> $document
     */
    private function buildPost(array $document, string $slug): Post|string
    {
        $author = $this->resolveAuthor($document['author'] ?? null);
        if (is_string($author)) {
            return $author;
        }

        $data = $this->validateData($this->contentTypeFields(ContentType::Posts), $document['data'] ?? []);
        if ($data['error'] !== null) {
            return $data['error'];
        }

        $posts = $this->fetchTable('Posts');
        $post = $posts->find()->where(['Posts.slug' => $slug])->first() ?? $posts->newEmptyEntity();
        $posts->patchEntity($post, [
            'title' => $document['title'] ?? null,
            'slug' => $slug,
            'status' => $document['status'] ?? null,
            'excerpt' => $document['excerpt'] ?? null,
            'body' => $document['body'] ?? null,
            'published_at' => $document['published_at'] ?? null,
            'comments_enabled' => $document['comments_enabled'] ?? false,
            'data' => $data['data'],
        ]);
        $post->author_id = $author;

        return $post;
    }

    /**
     * @param array<array-key, mixed> $document
     */
    private function buildPage(array $document, string $slug): Page|string
    {
        $author = $this->resolveAuthor($document['author'] ?? null);
        if (is_string($author)) {
            return $author;
        }

        $pages = $this->fetchTable('Pages');

        $parentPath = $this->stringValue($document['parent'] ?? null);
        $parent = $parentPath === '' ? null : $pages->findPublicByPath($parentPath, includeUnpublished: true);
        if ($parentPath !== '' && !$parent instanceof Page) {
            return sprintf('Unknown parent page: "%s".', $parentPath);
        }

        $data = $this->validateData($this->contentTypeFields(ContentType::Pages), $document['data'] ?? []);
        if ($data['error'] !== null) {
            return $data['error'];
        }

        $parentId = $parent?->id;
        $page = $pages->find()->where(['Pages.slug' => $slug, 'Pages.parent_id IS' => $parentId])->first() ?? $pages->newEmptyEntity();
        $pages->patchEntity($page, [
            'title' => $document['title'] ?? null,
            'slug' => $slug,
            'status' => $document['status'] ?? null,
            'body' => $document['body'] ?? null,
            'template' => $document['template'] ?? 'default',
            'visibility' => $document['visibility'] ?? 'public',
            'published_at' => $document['published_at'] ?? null,
            'comments_enabled' => $document['comments_enabled'] ?? false,
            'position' => $document['position'] ?? 0,
            'data' => $data['data'],
        ]);
        $page->author_id = $author;
        $page->parent_id = $parentId;

        return $page;
    }

    /**
     * @param array<array-key, mixed> $document
     */
    private function pageIdentifier(array $document, string $slug): string
    {
        $parentPath = $this->stringValue($document['parent'] ?? null);

        return $parentPath === '' ? $slug : $parentPath . '/' . $slug;
    }

    /**
     * @param array<array-key, mixed> $document
     */
    private function buildEntry(Collection $collection, array $document, string $slug): CollectionEntry|string
    {
        $author = $this->resolveAuthor($document['author'] ?? null);
        if (is_string($author)) {
            return $author;
        }

        $rawData = is_array($document['data'] ?? null) ? $document['data'] : [];
        $references = is_array($document['references'] ?? null) ? $document['references'] : [];

        $data = $this->validateData($collection->fields, array_merge($rawData, $this->resolveReferences($collection, $references)));
        if ($data['error'] !== null) {
            return $data['error'];
        }

        $entries = $this->fetchTable('CollectionEntries');
        $entry = $entries->find()->where(['CollectionEntries.collection_id' => $collection->id, 'CollectionEntries.slug' => $slug])->first()
            ?? $entries->newEmptyEntity();
        $entries->patchEntity($entry, [
            'title' => $document['title'] ?? null,
            'slug' => $slug,
            'status' => $document['status'] ?? null,
            'published_at' => $document['published_at'] ?? null,
            'comments_enabled' => $document['comments_enabled'] ?? false,
            'data' => $data['data'],
        ]);
        $entry->author_id = $author;
        $entry->collection_id = $collection->id;

        return $entry;
    }

    /**
     * @return array{parsed: array<array-key, mixed>, hash: string}|string
     */
    private function readDocument(string $file): array|string
    {
        $raw = file_get_contents($file);
        if ($raw === false) {
            return 'File is unreadable.';
        }

        try {
            $parsed = Yaml::parse($raw);
        } catch (ParseException $exception) {
            return $exception->getMessage();
        }

        if (!is_array($parsed)) {
            return 'File must be a YAML mapping.';
        }

        return ['parsed' => $parsed, 'hash' => hash('sha256', $raw)];
    }

    private function resolveAuthor(mixed $email): int|string
    {
        $address = $this->stringValue($email);
        if ($address === '') {
            return 'Missing "author" email.';
        }

        $user = $this->fetchTable('Users')->find()->where(['Users.email' => $address])->first();
        if ($user === null) {
            return sprintf('Unknown author email: "%s".', $address);
        }

        return $user->id;
    }

    /**
     * @param array<array-key, mixed> $fields
     * @return array{data: array<array-key, mixed>, error: string|null}
     */
    private function validateData(array $fields, mixed $raw): array
    {
        $rawArray = is_array($raw) ? $raw : [];
        if ($fields === []) {
            return ['data' => $rawArray, 'error' => null];
        }

        $result = new FieldDataValidator()->validate($fields, $rawArray);
        $field = array_key_first($result['errors']);

        return ['data' => $result['data'], 'error' => $field === null ? null : $field . ': ' . $result['errors'][$field]];
    }

    /**
     * @param array<array-key, mixed> $references
     * @return array<string, int|list<int>|null>
     */
    private function resolveReferences(Collection $collection, array $references): array
    {
        $merged = [];
        foreach ($collection->fields as $field) {
            if ($field['type'] !== CollectionFieldType::Reference) {
                continue;
            }

            $name = $field['name'];
            $slugs = is_array($references[$name] ?? null) ? $references[$name] : [];
            $ids = $this->referenceIds($slugs, $field['target'] ?? '');
            $merged[$name] = ($field['cardinality'] ?? 'one') === 'many' ? $ids : ($ids[0] ?? null);
        }

        return $merged;
    }

    /**
     * @param array<array-key, mixed> $slugs
     * @return list<int>
     */
    private function referenceIds(array $slugs, string $targetSlug): array
    {
        $wanted = array_values(array_filter(
            array_map($this->stringValue(...), $slugs),
            static fn (string $slug): bool => $slug !== '',
        ));
        if ($wanted === [] || $targetSlug === '') {
            return [];
        }

        $idBySlug = array_flip($this->fetchTable('CollectionEntries')
            ->find('list', keyField: 'id', valueField: 'slug')
            ->find('byCollectionSlug', collectionSlug: $targetSlug)
            ->where(['CollectionEntries.slug IN' => $wanted])
            ->toArray());
        $ids = array_map(static fn (string $slug): int|string|null => $idBySlug[$slug] ?? null, $wanted);

        return array_values(array_filter($ids, is_int(...)));
    }

    /**
     * @return array<array-key, mixed>
     */
    private function contentTypeFields(ContentType $type): array
    {
        return $this->fetchTable('ContentTypeFieldSchemas')->schemaFor($type)->fields;
    }

    /**
     * @return list<string>
     */
    private function yamlFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];
        foreach ($iterator as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && $entry->getExtension() === 'yml') {
                $files[] = $entry->getPathname();
            }
        }

        usort($files, static function (string $left, string $right) use ($directory): int {
            $depth = substr_count(substr($left, strlen($directory)), '/') <=> substr_count(substr($right, strlen($directory)), '/');

            return $depth !== 0 ? $depth : $left <=> $right;
        });

        return $files;
    }

    private function failed(string $subjectType, string $identifier, string $error): ContentImportResult
    {
        return new ContentImportResult($subjectType, $identifier, ContentImportOutcome::Failed, $error);
    }

    private function firstError(EntityInterface $entity): string
    {
        foreach ($entity->getErrors() as $field => $rules) {
            foreach ((array) $rules as $message) {
                if (is_string($message)) {
                    return $field . ': ' . $message;
                }
            }
        }

        return 'Invalid content record.';
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
