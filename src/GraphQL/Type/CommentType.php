<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\Model\Entity\Comment;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class CommentType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Comment',
            'description' => 'An approved, public comment.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'authorName' => Type::nonNull(Type::string()),
                'body' => Type::nonNull(Type::string()),
                'status' => Type::nonNull(Registry::commentStatus()),
                'createdAt' => Type::nonNull(Registry::dateTime()),
                'children' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::comment()))),
                    'resolve' => static function (Comment $comment): array {
                        $children = $comment->get('children');

                        return is_array($children) ? $children : [];
                    },
                ],
            ],
        ]);
    }
}
