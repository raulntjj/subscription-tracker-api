<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\CursorPaginator;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
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
        $startTime = microtime(true);

        $this->logger()->debug('Finding subscription with cursor pagination', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        $query = DB::table('subscriptions');

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

        $paginator = $query->cursorPaginate(
            perPage: $perPage,
            cursor: $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null
        );

        $paginator->through(function ($itemData) {
            $itemCacheKey = "subscription:{$itemData->id}";

            $cachedData = $this->cache()->remember(
                $itemCacheKey,
                3600,
                function () use ($itemData) {
                    return (array) $itemData;
                }
            );

            return SubscriptionDTO::fromDatabase($cachedData);
        });

        $duration = microtime(true) - $startTime;

        $this->logger()->info('Subscription cursor paginated retrieved', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'has_more' => $paginator->hasMorePages(),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return $paginator;
    }
}
