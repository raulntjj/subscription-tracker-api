<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Illuminate\Pagination\CursorPaginator;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar subscription com cursor pagination (mobile)
 * Suporta busca e ordenação dinâmica
 */
final readonly class FindSubscriptionsCursorPaginatedQuery
{
    use Loggable;
    use Cacheable;

    private const DEFAULT_PER_PAGE = 20;
    private const CACHE_TTL = 300; // 5 minutos

    protected function cacheTags(): array
    {
        return ['subscriptions'];
    }

    public function execute(
        ?string $cursor = null,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): CursorPaginator {
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $sortKey = $sort ? $sort->cacheKey() : 'sort:default';
        $cursorKey = $cursor ?? 'cursor:none';
        $cacheKey = "subscriptions:cursor_paginated:cursor:{$cursorKey}:per_page:{$perPage}:{$searchKey}:{$sortKey}";

        $this->logger()->debug('Finding subscription with cursor pagination', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($cursor, $perPage, $search, $sort) {
                $query = SubscriptionModel::query();

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
                    $query->orderBy('id', 'desc');
                } else {
                    $query->orderBy('created_at', 'desc');
                    $query->orderBy('id', 'desc');
                }

                // UMA ÚNICA query com cursorPaginate() - resolve N+1
                $paginator = $query->cursorPaginate(
                    perPage: $perPage,
                    cursor: $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null
                );

                // Converte para DTOs
                $items = $paginator->getCollection()
                    ->map(fn ($model) => SubscriptionDTO::fromDatabase($model))
                    ->all();

                return new CursorPaginator(
                    items: $items,
                    perPage: $paginator->perPage(),
                    cursor: $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null,
                    options: [
                        'path' => request()->url(),
                        'parameters' => [
                            'next' => $paginator->nextCursor()?->encode(),
                            'prev' => $paginator->previousCursor()?->encode(),
                        ],
                    ]
                );
            }
        );
    }
}
