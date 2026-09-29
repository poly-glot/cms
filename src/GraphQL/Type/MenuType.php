<?php

declare(strict_types=1);

namespace App\GraphQL\Type;

use App\GraphQL\Registry;
use App\Model\Entity\Menu;
use Cake\ORM\Locator\LocatorAwareTrait;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

final class MenuType extends ObjectType
{
    use LocatorAwareTrait;

    public function __construct()
    {
        parent::__construct([
            'name' => 'Menu',
            'description' => 'A navigation menu.',
            'fields' => fn (): array => [
                'id' => Type::nonNull(Type::id()),
                'name' => Type::nonNull(Type::string()),
                'slug' => Type::nonNull(Type::string()),
                'items' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Registry::menuItem()))),
                    'resolve' => fn (Menu $menu): array => $this->fetchTable('MenuItems')->find('tree', menuId: $menu->id)->all()->toList(),
                ],
            ],
        ]);
    }
}
