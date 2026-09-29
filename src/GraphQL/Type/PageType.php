<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\Page;
use App\Model\Enum\ContentType;
use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class PageType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Page',
            'description' => 'A content page in the site tree.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'title' => Type::nonNull(Type::string()),
                'slug' => Type::nonNull(Type::string()),
                'body' => Type::string(),
                'status' => Type::nonNull(Registry::pageStatus()),
                'visibility' => Type::nonNull(Type::string()),
                'template' => Type::nonNull(Type::string()),
                'position' => Type::nonNull(Type::int()),
                'fullPath' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => static fn (Page $page, array $args, GraphqlContext $context): string => $context->loaders->pagePath($page),
                ],
                'publishedAt' => Registry::dateTime(),
                'commentsEnabled' => Type::nonNull(Type::boolean()),
                'author' => [
                    'type' => Registry::user(),
                    'resolve' => static fn (Page $page, array $args, GraphqlContext $context): Deferred => $context->loaders->loadAuthor($page->author_id),
                ],
                'parent' => [
                    'type' => Registry::page(),
                    'resolve' => static fn (Page $page, array $args, GraphqlContext $context): Deferred => $context->loaders->loadParentPage($page->parent_id),
                ],
                'children' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::page()))),
                    'resolve' => static fn (Page $page, array $args, GraphqlContext $context): Deferred => $context->loaders->loadChildPages($page->id),
                ],
                'tags' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::tag()))),
                    'resolve' => static fn (Page $page, array $args, GraphqlContext $context): Deferred => $context->loaders->loadTags('Pages', $page->id),
                ],
                'customFields' => Registry::json(),
                'fieldSchema' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::fieldSchemaField()))),
                    'resolve' => static fn (Page $page, array $args, GraphqlContext $context): array => $context->loaders->contentFieldSchema(ContentType::Pages),
                ],
                'createdAt' => Type::nonNull(Registry::dateTime()),
                'modifiedAt' => Type::nonNull(Registry::dateTime()),
            ],
        ]);
    }
}
