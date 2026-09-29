<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\Post;
use App\Model\Enum\ContentType;
use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class PostType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Post',
            'description' => 'A blog post.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'title' => Type::nonNull(Type::string()),
                'slug' => Type::nonNull(Type::string()),
                'excerpt' => Type::string(),
                'body' => Type::string(),
                'status' => Type::nonNull(Registry::postStatus()),
                'publishedAt' => Registry::dateTime(),
                'commentsEnabled' => Type::nonNull(Type::boolean()),
                'author' => [
                    'type' => Registry::user(),
                    'resolve' => static fn (Post $post, array $args, GraphqlContext $context): Deferred => $context->loaders->loadAuthor($post->author_id),
                ],
                'tags' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::tag()))),
                    'resolve' => static fn (Post $post, array $args, GraphqlContext $context): Deferred => $context->loaders->loadTags('Posts', $post->id),
                ],
                'customFields' => Registry::json(),
                'fieldSchema' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::fieldSchemaField()))),
                    'resolve' => static fn (Post $post, array $args, GraphqlContext $context): array => $context->loaders->contentFieldSchema(ContentType::Posts),
                ],
                'createdAt' => Type::nonNull(Registry::dateTime()),
                'modifiedAt' => Type::nonNull(Registry::dateTime()),
            ],
        ]);
    }
}
