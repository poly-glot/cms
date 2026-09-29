<?php

declare(strict_types=1);

namespace App\GraphQL\Type\Input;

use App\GraphQL\Registry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

final class EntryInput extends InputObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'EntryInput',
            'fields' => static fn (): array => [
                'title' => Type::string(),
                'slug' => Type::string(),
                'status' => Registry::postStatus(),
                'publishedAt' => Registry::dateTime(),
                'commentsEnabled' => Type::boolean(),
                'data' => Registry::json(),
            ],
        ]);
    }
}
