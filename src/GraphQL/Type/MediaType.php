<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\Media;
use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class MediaType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Media',
            'description' => 'A media library file.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'name' => Type::nonNull(Type::string()),
                'mime' => Type::nonNull(Type::string()),
                'kind' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => static fn (Media $media): string => $media->kind,
                ],
                'size' => Type::nonNull(Type::int()),
                'width' => Type::int(),
                'height' => Type::int(),
                'alt' => Type::string(),
                'url' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => static fn (Media $media, array $args, GraphqlContext $context): string => sprintf('/%s/media/%s', $context->workspaceSlug, $media->filename),
                ],
                'renditions' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::mediaRendition()))),
                    'resolve' => static fn (Media $media, array $args, GraphqlContext $context): Deferred => $context->loaders->loadRenditions($media->id),
                ],
                'createdAt' => Type::nonNull(Registry::dateTime()),
                'modifiedAt' => Type::nonNull(Registry::dateTime()),
            ],
        ]);
    }
}
