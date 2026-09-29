<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\GraphQL\Support\GraphqlContext;
use App\Model\Entity\MenuItem;
use App\Model\Entity\Page;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class MenuItemType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'MenuItem',
            'description' => 'A single navigation link.',
            'fields' => static fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'title' => Type::nonNull(Type::string()),
                'type' => Type::nonNull(Registry::menuItemType()),
                'url' => Type::string(),
                'target' => Type::nonNull(Type::string()),
                'opensInNewWindow' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'resolve' => static fn (MenuItem $item): bool => $item->opensInNewWindow,
                ],
                'page' => [
                    'type' => Registry::page(),
                    'resolve' => static fn (MenuItem $item, array $args, GraphqlContext $context): ?Page => self::linkedPage($item, $context),
                ],
                'children' => Type::nonNull(Type::listOf(Type::nonNull(Registry::menuItem()))),
            ],
        ]);
    }

    private static function linkedPage(MenuItem $item, GraphqlContext $context): ?Page
    {
        $page = $item->page;
        if (!$page instanceof Page) {
            return null;
        }

        if ($context->canPreview() || $page->statusEnum->isPubliclyVisible()) {
            return $page;
        }

        return null;
    }
}
