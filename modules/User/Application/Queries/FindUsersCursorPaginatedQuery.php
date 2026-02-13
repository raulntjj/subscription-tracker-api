<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Infrastructure\Persistence\Eloquent\UserModel;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar usuários com cursor pagination (mobile)
 * Suporta busca e ordenação dinâmica
 * 
 * CQRS: Queries podem usar Eloquent Models diretamente para leitura
 * Benefícios: soft deletes automático, casts, scopes, relations
 */
final readonly class FindUsersCursorPaginatedQuery
{
    use Loggable;
    use Cacheable;

    private const DEFAULT_PER_PAGE = 20;

    protected function cacheTags(): array
    {
        return ['users'];
    }

    public function execute(
        ?string $cursor = null,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): array {
        $startTime = microtime(true);
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $sortKey = $sort ? $sort->cacheKey() : 'sort:default';
        $cursorKey = $cursor ?? 'cursor:none';
        $cacheKey = "users:cursor_paginated:cursor:{$cursorKey}:per_page:{$perPage}:{$searchKey}:{$sortKey}";

        $this->logger()->debug('Finding users with cursor pagination', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        // Cache completo do resultado
        $result = $this->cache()->remember(
            $cacheKey,
            300,
            function () use ($cursor, $perPage, $search, $sort, $startTime) {
                $this->logger()->debug('Cache miss - fetching from database');

                $query = UserModel::query();

                // Aplica busca
                if ($search !== null && $search->hasSearch()) {
                    $query->where(function ($q) use ($search) {
                        foreach ($search->columns as $column) {
                            $q->orWhere($column, 'LIKE', "%{$search->term}%");
                        }
                    });
                }

                // Aplica ordenação
                if ($sort !== null && $sort->hasSorts()) {
                    foreach ($sort->sorts as $sortItem) {
                        $query->orderBy($sortItem['column'], $sortItem['direction']);
                    }
                    // Ordem secundária para estabilidade do cursor
                    $query->orderBy('id', 'desc');
                } else {
                    $query->orderBy('created_at', 'desc');
                    $query->orderBy('id', 'desc');
                }

                $paginator = $query->cursorPaginate(
                    perPage: $perPage,
                    cursor: $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null
                );

                // Converte para DTOs
                $users = $paginator->getCollection()
                    ->map(fn($model) => UserDTO::fromDatabase($model))
                    ->all();

                $duration = microtime(true) - $startTime;

                $this->logger()->info('Users cursor paginated retrieved', [
                    'cursor' => $cursor,
                    'per_page' => $perPage,
                    'search' => $search?->term,
                    'has_more' => $paginator->hasMorePages(),
                    'cache_hit' => false,
                    'duration_ms' => round($duration * 1000, 2),
                ]);

                return [
                    'users' => $users,
                    'pagination' => [
                        'next_cursor' => $paginator->nextCursor()?->encode(),
                        'prev_cursor' => $paginator->previousCursor()?->encode(),
                        'has_more' => $paginator->hasMorePages(),
                        'per_page' => $paginator->perPage(),
                    ],
                ];
            }
        );

        $duration = microtime(true) - $startTime;

        $this->logger()->info('Users cursor paginated returned from cache', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return $result;
    }
}
