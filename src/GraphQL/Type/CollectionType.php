<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\GraphQL\Support\Connection;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\Collection;
use Cake\ORM\Locator\LocatorAwareTrait;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class CollectionType extends ObjectType
{
    use LocatorAwareTrait;

    public function __construct()
    {
        parent::__construct([
            'name' => 'Collection',
            'description' => 'A custom content type and its field schema.',
            'fields' => fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'name' => Type::nonNull(Type::string()),
                'slug' => Type::nonNull(Type::string()),
                'description' => Type::string(),
                'fieldSchema' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::fieldSchemaField()))),
                    'resolve' => static fn (Collection $collection): array => $collection->fields,
                ],
                'entries' => [
                    'type' => Type::nonNull(Registry::collectionEntryConnection()),
                    'args' => ['page' => Type::int(), 'perPage' => Type::int()],
                    'resolve' => $this->entries(...),
                ],
            ],
        ]);
    }

    /**
     * @param array<array-key, mixed> $args
     * @return array<string, mixed>
     */
    public function entries(Collection $collection, array $args, GraphqlContext $context): array
    {
        $query = $this->fetchTable('CollectionEntries')
            ->find('forCollection', collectionId: $collection->id)
            ->orderByDesc('CollectionEntries.published_at')
            ->orderByDesc('CollectionEntries.created');

        return Connection::fromArgs($context->canPreview() ? $query : $query->find('live'), $args);
    }
}
