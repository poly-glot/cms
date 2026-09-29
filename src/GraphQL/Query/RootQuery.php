<?php

declare(strict_types=1);

namespace App\GraphQL\Query;

use App\GraphQL\Registry;
use App\GraphQL\Support\Connection;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\Collection;
use App\Model\Table\PagesTable;
use App\Model\Table\SettingsTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class RootQuery extends ObjectType
{
    use LocatorAwareTrait;

    private const array COMMENTABLE_TYPES = ['Pages', 'Posts', 'CollectionEntries'];

    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => fn (): array => [
                'post' => [
                    'type' => Registry::post(),
                    'args' => ['id' => Type::id(), 'slug' => Type::string()],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): ?EntityInterface => $this->resolvePost($args, $context),
                ],
                'posts' => [
                    'type' => Type::nonNull(Registry::postConnection()),
                    'args' => ['page' => Type::int(), 'perPage' => Type::int(), 'tag' => Type::string()],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): array => $this->resolvePosts($args, $context),
                ],
                'page' => [
                    'type' => Registry::page(),
                    'args' => ['id' => Type::id(), 'path' => Type::string()],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): ?EntityInterface => $this->resolvePage($args, $context),
                ],
                'pages' => [
                    'type' => Type::nonNull(Registry::pageConnection()),
                    'args' => ['parentId' => Type::id(), 'page' => Type::int(), 'perPage' => Type::int()],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): array => $this->resolvePages($args, $context),
                ],
                'collection' => [
                    'type' => Registry::collection(),
                    'args' => ['slug' => Type::nonNull(Type::string())],
                    'resolve' => fn (mixed $root, array $args): ?EntityInterface => $this->collectionFromArgs($args, 'slug'),
                ],
                'collections' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::collection()))),
                    'resolve' => $this->resolveCollections(...),
                ],
                'entry' => [
                    'type' => Registry::collectionEntry(),
                    'args' => ['collection' => Type::nonNull(Type::string()), 'slug' => Type::string(), 'id' => Type::id()],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): ?EntityInterface => $this->resolveEntry($args, $context),
                ],
                'entries' => [
                    'type' => Type::nonNull(Registry::collectionEntryConnection()),
                    'args' => ['collection' => Type::nonNull(Type::string()), 'page' => Type::int(), 'perPage' => Type::int()],
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): array => $this->resolveEntries($args, $context),
                ],
                'menu' => [
                    'type' => Registry::menu(),
                    'args' => ['slug' => Type::nonNull(Type::string())],
                    'resolve' => fn (mixed $root, array $args): ?EntityInterface => $this->resolveMenu($args),
                ],
                'menus' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::menu()))),
                    'resolve' => $this->resolveMenus(...),
                ],
                'media' => [
                    'type' => Registry::media(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args): ?EntityInterface => $this->resolveMedia($args),
                ],
                'mediaLibrary' => [
                    'type' => Type::nonNull(Registry::mediaConnection()),
                    'args' => ['page' => Type::int(), 'perPage' => Type::int()],
                    'resolve' => fn (mixed $root, array $args): array => $this->resolveMediaLibrary($args),
                ],
                'tags' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::tag()))),
                    'resolve' => $this->resolveTags(...),
                ],
                'comments' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::comment()))),
                    'args' => ['commentableType' => Type::nonNull(Type::string()), 'commentableId' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args): array => $this->resolveComments($args),
                ],
                'blocks' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::block()))),
                    'resolve' => $this->resolveBlocks(...),
                ],
                'block' => [
                    'type' => Registry::block(),
                    'args' => ['id' => Type::nonNull(Type::id())],
                    'resolve' => fn (mixed $root, array $args): ?EntityInterface => $this->resolveBlock($args),
                ],
                'settings' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::setting()))),
                    'resolve' => $this->resolveSettings(...),
                ],
                'workspace' => [
                    'type' => Type::nonNull(Registry::workspace()),
                    'resolve' => fn (mixed $root, array $args, GraphqlContext $context): ?EntityInterface => $this->resolveWorkspace($context),
                ],
            ],
        ]);
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function resolvePost(array $args, GraphqlContext $context): ?EntityInterface
    {
        $query = $this->visible('Posts', $context);
        $slug = $this->stringArg($args, 'slug');
        $id = $this->idArg($args, 'id');

        if ($slug !== null) {
            $query->where(['Posts.slug' => $slug]);
        } elseif ($id !== null) {
            $query->where(['Posts.id' => $id]);
        } else {
            return null;
        }

        return $query->first();
    }

    /**
     * @param array<array-key, mixed> $args
     * @return array<string, mixed>
     */
    private function resolvePosts(array $args, GraphqlContext $context): array
    {
        $query = $this->visible('Posts', $context);
        $tag = $this->stringArg($args, 'tag');
        if ($tag !== null) {
            $query = $query->find('byTags', slugs: [$tag]);
        }

        $query->orderByDesc('Posts.published_at')->orderByDesc('Posts.created');

        return Connection::fromArgs($query, $args);
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function resolvePage(array $args, GraphqlContext $context): ?EntityInterface
    {
        $path = $this->stringArg($args, 'path');
        if ($path !== null) {
            /** @var PagesTable $pages */
            $pages = $this->fetchTable('Pages');

            return $pages->findPublicByPath($path, $context->canPreview());
        }

        $id = $this->idArg($args, 'id');
        if ($id === null) {
            return null;
        }

        return $this->visible('Pages', $context)->where(['Pages.id' => $id])->first();
    }

    /**
     * @param array<array-key, mixed> $args
     * @return array<string, mixed>
     */
    private function resolvePages(array $args, GraphqlContext $context): array
    {
        $query = $this->visible('Pages', $context);
        $parentId = $this->idArg($args, 'parentId');
        if ($parentId !== null) {
            $query->where(['Pages.parent_id' => $parentId]);
        }

        $query->orderByAsc('Pages.position')->orderByAsc('Pages.id');

        return Connection::fromArgs($query, $args);
    }

    /**
     * @return list<EntityInterface>
     */
    private function resolveCollections(): array
    {
        return array_values($this->fetchTable('Collections')->find()->orderByAsc('Collections.name')->all()->toList());
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function resolveEntry(array $args, GraphqlContext $context): ?EntityInterface
    {
        $collection = $this->collectionFromArgs($args, 'collection');
        if (!$collection instanceof Collection) {
            return null;
        }

        $query = $this->visible('CollectionEntries', $context)->find('forCollection', collectionId: $collection->id);
        $slug = $this->stringArg($args, 'slug');
        $id = $this->idArg($args, 'id');
        if ($slug !== null) {
            $query->where(['CollectionEntries.slug' => $slug]);
        } elseif ($id !== null) {
            $query->where(['CollectionEntries.id' => $id]);
        } else {
            return null;
        }

        return $query->first();
    }

    /**
     * @param array<array-key, mixed> $args
     * @return array<string, mixed>
     */
    private function resolveEntries(array $args, GraphqlContext $context): array
    {
        $collection = $this->collectionFromArgs($args, 'collection');
        if (!$collection instanceof Collection) {
            return Connection::empty();
        }

        return Registry::collection()->entries($collection, $args, $context);
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function resolveMenu(array $args): ?EntityInterface
    {
        $slug = $this->stringArg($args, 'slug');
        $menu = $slug === null ? null : $this->fetchTable('Menus')->findBySlug($slug)->first();

        return $menu instanceof EntityInterface ? $menu : null;
    }

    /**
     * @return list<EntityInterface>
     */
    private function resolveMenus(): array
    {
        return array_values($this->fetchTable('Menus')->find()->orderByAsc('Menus.name')->all()->toList());
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function resolveMedia(array $args): ?EntityInterface
    {
        $id = $this->idArg($args, 'id');

        return $id === null ? null : $this->fetchTable('Media')->find()->where(['Media.id' => $id])->first();
    }

    /**
     * @param array<array-key, mixed> $args
     * @return array<string, mixed>
     */
    private function resolveMediaLibrary(array $args): array
    {
        return Connection::fromArgs($this->fetchTable('Media')->find()->orderByDesc('Media.created'), $args);
    }

    /**
     * @return list<EntityInterface>
     */
    private function resolveTags(): array
    {
        return array_values($this->fetchTable('Tags')->find()->orderByAsc('Tags.label')->all()->toList());
    }

    /**
     * @param array<array-key, mixed> $args
     * @return list<EntityInterface>
     */
    private function resolveComments(array $args): array
    {
        $type = $this->stringArg($args, 'commentableType');
        $id = $this->idArg($args, 'commentableId');
        if ($type === null || $id === null || !in_array($type, self::COMMENTABLE_TYPES, true)) {
            return [];
        }

        return array_values($this->fetchTable('Comments')
            ->find('approvedFor', commentableType: $type, commentableId: $id)
            ->all()
            ->toList());
    }

    /**
     * @return list<EntityInterface>
     */
    private function resolveBlocks(): array
    {
        return array_values($this->fetchTable('Blocks')->find()->orderByDesc('Blocks.created')->all()->toList());
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function resolveBlock(array $args): ?EntityInterface
    {
        $id = $this->idArg($args, 'id');

        return $id === null ? null : $this->fetchTable('Blocks')->find()->where(['Blocks.id' => $id])->first();
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    private function resolveSettings(): array
    {
        /** @var SettingsTable $settings */
        $settings = $this->fetchTable('Settings');

        $out = [];
        foreach ($settings->all() as $key => $value) {
            $out[] = ['key' => $key, 'value' => $value];
        }

        return $out;
    }

    private function resolveWorkspace(GraphqlContext $context): ?EntityInterface
    {
        return $this->fetchTable('Workspaces')->find()->where(['Workspaces.id' => $context->workspaceId])->first();
    }

    /**
     * @return SelectQuery<EntityInterface>
     */
    private function visible(string $tableName, GraphqlContext $context): SelectQuery
    {
        $query = $this->fetchTable($tableName)->find();

        return $context->canPreview() ? $query : $query->find('live');
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function collectionFromArgs(array $args, string $key): ?EntityInterface
    {
        $slug = $this->stringArg($args, $key);
        $collection = $slug === null ? null : $this->fetchTable('Collections')->findBySlug($slug)->first();

        return $collection instanceof EntityInterface ? $collection : null;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function stringArg(array $args, string $key): ?string
    {
        $value = $args[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private function idArg(array $args, string $key): ?int
    {
        $value = $args[$key] ?? null;

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }
}
