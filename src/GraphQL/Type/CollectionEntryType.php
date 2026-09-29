<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\CollectionEntry;
use Cake\ORM\Locator\LocatorAwareTrait;
use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class CollectionEntryType extends ObjectType
{
    use LocatorAwareTrait;

    public function __construct()
    {
        parent::__construct([
            'name' => 'CollectionEntry',
            'description' => 'An entry in a collection.',
            'fields' => fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'title' => Type::nonNull(Type::string()),
                'slug' => Type::nonNull(Type::string()),
                'status' => Type::nonNull(Registry::postStatus()),
                'publishedAt' => Registry::dateTime(),
                'commentsEnabled' => Type::nonNull(Type::boolean()),
                'author' => [
                    'type' => Registry::user(),
                    'resolve' => static fn (CollectionEntry $entry, array $args, GraphqlContext $context): Deferred => $context->loaders->loadAuthor($entry->author_id),
                ],
                'collection' => [
                    'type' => Registry::collection(),
                    'resolve' => fn (CollectionEntry $entry): mixed => $this->fetchTable('Collections')->find()->where(['Collections.id' => $entry->collection_id])->first(),
                ],
                'data' => Registry::json(),
                'references' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::referenceGroup()))),
                    'resolve' => static fn (CollectionEntry $entry, array $args, GraphqlContext $context): Deferred => $context->loaders->loadReferences($entry->id),
                ],
                'createdAt' => Type::nonNull(Registry::dateTime()),
                'modifiedAt' => Type::nonNull(Registry::dateTime()),
            ],
        ]);
    }
}
