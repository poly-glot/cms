<?php

declare(strict_types=1);

namespace App\GraphQL\Type\Input;

use App\GraphQL\Registry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

final class PageInput extends InputObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'PageInput',
            'fields' => static fn (): array => [
                'title' => Type::string(),
                'slug' => Type::string(),
                'body' => Type::string(),
                'status' => Registry::pageStatus(),
                'publishedAt' => Registry::dateTime(),
                'template' => Type::string(),
                'visibility' => Type::string(),
                'commentsEnabled' => Type::boolean(),
                'parentId' => Type::id(),
                'position' => Type::int(),
                'customFields' => Registry::json(),
            ],
        ]);
    }
}
