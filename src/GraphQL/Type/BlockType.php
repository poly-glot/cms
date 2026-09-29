<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\Block;
use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class BlockType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Block',
            'description' => 'A reusable content block.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'name' => Type::nonNull(Type::string()),
                'blockType' => Type::nonNull(Registry::blockType()),
                'data' => Registry::json(),
                'author' => [
                    'type' => Registry::user(),
                    'resolve' => static fn (Block $block, array $args, GraphqlContext $context): Deferred => $context->loaders->loadAuthor($block->author_id),
                ],
                'createdAt' => Type::nonNull(Registry::dateTime()),
                'modifiedAt' => Type::nonNull(Registry::dateTime()),
            ],
        ]);
    }
}
