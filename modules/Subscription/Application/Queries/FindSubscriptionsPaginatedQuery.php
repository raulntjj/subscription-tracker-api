<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar subscription com paginação offset (web)
 * Suporta busca e ordenação dinâmica
 */
final readonly class FindSubscriptionsPaginatedQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 300; // 5 minutos
    private const DEFAULT_PER_PAGE = 15;

    protected function cacheTags(): array
    {
        return ['subscriptions'];
    }

    public function execute(
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): LengthAwarePaginator {
        $startTime = microtime(true);
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $sortKey = $sort ? $sort->cacheKey() : 'sort:default';
        $cacheKey = "subscriptions:paginated:page:{$page}:per_page:{$perPage}:{$searchKey}:{$sortKey}";

        $this->logger()->debug('Finding subscription with pagination', [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        // Cache da paginação (apenas IDs e total)
        $result = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($page, $perPage, $search, $sort, $startTime) {
                $this->logger()->debug('Cache miss - fetching IDs from database');

                $baseQuery = DB::table('subscriptions');

                // Aplica busca
                if ($search !== null && $search->hasSearch()) {
                    $baseQuery->where(function ($q) use ($search) {
                        foreach ($search->columns as $column) {
                            $q->orWhere($column, 'LIKE', "%{$search->term}%");
                        }
                    });
                }

                $total = (clone $baseQuery)->count();

                // Aplica ordenação
                if ($sort !== null && $sort->hasSorts()) {
                    foreach ($sort->sorts as $sortItem) {
                        $baseQuery->orderBy($sortItem['column'], $sortItem['direction']);
                    }
                } else {
                    $baseQuery->orderBy('created_at', 'desc');
                }

                $ids = $baseQuery
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->pluck('id')
                    ->all();

                $duration = microtime(true) - $startTime;

                $this->logger()->info('Subscription IDs paginated retrieved', [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'search' => $search?->term,
                    'cache_hit' => false,
                    'duration_ms' => round($duration * 1000, 2),
                ]);

                return [
                    'ids' => $ids,
                    'total' => $total,
                ];
            }
        );

        // Busca cada item do cache individual
        $items = [];
        foreach ($result['ids'] as $itemId) {
            $itemCacheKey = "subscription:{$itemId}";

            $itemData = $this->cache()->remember(
                $itemCacheKey,
                3600,
                function () use ($itemId) {
                    $data = DB::table('subscriptions')
                        ->where('id', $itemId)
                        ->first();

                    return $data ? (array) $data : null;
                }
            );

            if ($itemData !== null) {
                $items[] = SubscriptionDTO::fromDatabase($itemData);
            }
        }

        $duration = microtime(true) - $startTime;

        $this->logger()->info('Subscription paginated returned', [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $result['total'],
            'search' => $search?->term,
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return new LengthAwarePaginator(
            items: $items,
            total: $result['total'],
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
