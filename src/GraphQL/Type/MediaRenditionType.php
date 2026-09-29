<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\MediaRendition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class MediaRenditionType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'MediaRendition',
            'description' => 'A resized variant of a media file.',
            'fields' => static fn (): array => [
                'name' => Type::nonNull(Type::string()),
                'width' => Type::int(),
                'height' => Type::int(),
                'size' => Type::nonNull(Type::int()),
                'url' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => static fn (MediaRendition $rendition, array $args, GraphqlContext $context): string => sprintf('/%s/media/%s', $context->workspaceSlug, $rendition->filename),
                ],
            ],
        ]);
    }
}
