<?php

declare(strict_types=1);

namespace App\Model\Enum;

enum ContentType: string
{
    case Pages = 'Pages';
    case Posts = 'Posts';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return list<string>
     */
    public function reservedFieldNames(): array
    {
        return match ($this) {
            self::Pages => [
                'id', 'workspace_id', 'author_id', 'title', 'slug', 'body', 'status',
                'template', 'visibility', 'parent_id', 'position', 'comments_enabled',
                'published_at', 'data', 'created', 'modified',
            ],
            self::Posts => [
                'id', 'workspace_id', 'author_id', 'title', 'slug', 'excerpt', 'body',
                'status', 'comments_enabled', 'published_at', 'data', 'created', 'modified',
            ],
        };
    }

    /**
     * @return list<CollectionFieldType>
     */
    public function allowedFieldTypes(): array
    {
        return array_values(array_filter(
            CollectionFieldType::cases(),
            static fn (CollectionFieldType $type): bool => $type !== CollectionFieldType::Reference
                && $type !== CollectionFieldType::Tags,
        ));
    }
}
