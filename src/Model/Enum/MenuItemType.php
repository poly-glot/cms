<?php

declare(strict_types=1);

namespace App\Model\Enum;

enum MenuItemType: string
{
    case Page = 'page';
    case Url = 'url';
}
