<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\Entity\Collection;
use App\Model\Entity\Tag;
use App\Model\Entity\Workspace;
use App\Model\Enum\CollectionFieldType;
use App\Service\FieldSchema\FieldDataValidator;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Symfony\Component\Yaml\Yaml;

final class ContentExporter
{
    use LocatorAwareTrait;

    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param list<ContentKind> $kinds
     * @return list<string>
     */
    public function export(string $root, string $workspaceSlug, array $kinds, string $collectionSlug = ''): array
    {
        return $this->fetchTable('Workspaces')->runAsAdmin($workspaceSlug, function (Workspace $workspace) use ($root, $kinds, $collectionSlug): array {
            $base = rtrim($root, '/') . '/' . $workspace->slug;

            $written = [];
            foreach ($kinds as $kind) {
                $written = [...$written, ...match ($kind) {
                    ContentKind::Posts => $this->exportPosts($base),
                    ContentKind::Pages => $this->exportPages($base),
                    ContentKind::Entries => $this->exportEntries($base, $collectionSlug),
                }];
            }

            return $written;
        });
    }

    /**
     * @return list<string>
     */
    private function exportPosts(string $base): array
    {
        $directory = $base . '/posts';

        $posts = $this->fetchTable('Posts')->find()
            ->contain(['Authors', 'Tags'])
            ->orderByAsc('Posts.slug')
            ->all();

        $written = [];
        foreach ($posts as $post) {
            $written[] = $this->writeFile($directory . '/' . $post->slug . '.yml', [
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'published_at' => $this->formatDate($post->published_at),
                'comments_enabled' => $post->comments_enabled,
                'author' => $post->author?->email,
                'tags' => $this->tagSlugs($post->tags),
                'data' => $post->data,
            ]);
        }

        return $written;
    }

    /**
     * @return list<string>
     */
    private function exportPages(string $base): array
    {
        $directory = $base . '/pages';
        $pagesTable = $this->fetchTable('Pages');

        $pages = $pagesTable->find()
            ->contain(['Authors', 'Tags'])
            ->orderByAsc('Pages.id')
            ->all();

        $paths = $pagesTable->pathsById();

        $written = [];
        foreach ($pages as $page) {
            $parentPath = $page->parent_id === null ? null : ($paths[$page->parent_id] ?? null);

            $written[] = $this->writeFile($directory . '/' . $paths[$page->id] . '.yml', [
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
                'body' => $page->body,
                'template' => $page->template,
                'visibility' => $page->visibility,
                'published_at' => $this->formatDate($page->published_at),
                'comments_enabled' => $page->comments_enabled,
                'position' => $page->position,
                'parent' => $parentPath,
                'author' => $page->author?->email,
                'tags' => $this->tagSlugs($page->tags),
                'data' => $page->data,
            ]);
        }

        return $written;
    }

    /**
     * @return list<string>
     */
    private function exportEntries(string $base, string $collectionSlug): array
    {
        $written = [];
        foreach ($this->fetchTable('Collections')->find('forSync', slug: $collectionSlug) as $collection) {
            $directory = $base . '/collections/' . $collection->slug;

            $entries = $this->fetchTable('CollectionEntries')->find()
                ->where(['CollectionEntries.collection_id' => $collection->id])
                ->contain(['Authors'])
                ->orderByAsc('CollectionEntries.slug')
                ->all();

            foreach ($entries as $entry) {
                [$references, $data] = $this->splitReferences($collection, $entry->data);

                $written[] = $this->writeFile($directory . '/' . $entry->slug . '.yml', [
                    'title' => $entry->title,
                    'slug' => $entry->slug,
                    'status' => $entry->status,
                    'published_at' => $this->formatDate($entry->published_at),
                    'comments_enabled' => $entry->comments_enabled,
                    'author' => $entry->author?->email,
                    'references' => $references,
                    'data' => $data,
                ]);
            }
        }

        return $written;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0: array<string, list<string>>, 1: array<string, mixed>}
     */
    private function splitReferences(Collection $collection, array $data): array
    {
        $references = [];
        $remaining = $data;

        foreach ($collection->fields as $field) {
            if ($field['type'] !== CollectionFieldType::Reference) {
                continue;
            }

            $name = $field['name'];
            $references[$name] = $this->referenceSlugs(FieldDataValidator::idList($data[$name] ?? null), $field['target'] ?? '');
            unset($remaining[$name]);
        }

        return [$references, $remaining];
    }

    /**
     * @param list<int> $ids
     * @return list<string>
     */
    private function referenceSlugs(array $ids, string $targetSlug): array
    {
        if ($ids === [] || $targetSlug === '') {
            return [];
        }

        $slugById = $this->fetchTable('CollectionEntries')
            ->find('list', keyField: 'id', valueField: 'slug')
            ->find('byCollectionSlug', collectionSlug: $targetSlug)
            ->where(['CollectionEntries.id IN' => $ids])
            ->toArray();
        $slugs = array_map(static fn (int $id): ?string => $slugById[$id] ?? null, $ids);

        return array_values(array_filter($slugs, is_string(...)));
    }

    /**
     * @param array<int, Tag> $tags
     * @return list<string>
     */
    private function tagSlugs(array $tags): array
    {
        $slugs = array_map(static fn (Tag $tag): string => $tag->slug, $tags);
        sort($slugs);

        return $slugs;
    }

    private function formatDate(?DateTime $value): ?string
    {
        return $value?->format(self::DATETIME_FORMAT);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeFile(string $path, array $payload): string
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        $yaml = Yaml::dump($payload, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        file_put_contents($path, $yaml);

        return $path;
    }
}
