<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;

final class Connection
{
    private const int MAX_PER_PAGE = 100;

    private const int DEFAULT_PER_PAGE = 20;

    /**
     * @param SelectQuery<EntityInterface> $query
     * @param array<array-key, mixed> $args
     * @return array{items: list<EntityInterface>, pageInfo: array{page: int, perPage: int, total: int, hasNextPage: bool}}
     */
    public static function fromArgs(SelectQuery $query, array $args): array
    {
        $page = max(1, self::intArg($args, 'page', 1));
        $perPage = max(1, min(self::MAX_PER_PAGE, self::intArg($args, 'perPage', self::DEFAULT_PER_PAGE)));

        $total = $query->count();
        $items = array_values($query->limit($perPage)->offset(($page - 1) * $perPage)->all()->toList());

        return [
            'items' => $items,
            'pageInfo' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'hasNextPage' => $page * $perPage < $total,
            ],
        ];
    }

    /**
     * @return array{items: list<EntityInterface>, pageInfo: array{page: int, perPage: int, total: int, hasNextPage: bool}}
     */
    public static function empty(): array
    {
        return [
            'items' => [],
            'pageInfo' => ['page' => 1, 'perPage' => self::DEFAULT_PER_PAGE, 'total' => 0, 'hasNextPage' => false],
        ];
    }

    /**
     * @param array<array-key, mixed> $args
     */
    private static function intArg(array $args, string $key, int $default): int
    {
        $value = $args[$key] ?? null;

        return is_int($value) ? $value : $default;
    }
}
