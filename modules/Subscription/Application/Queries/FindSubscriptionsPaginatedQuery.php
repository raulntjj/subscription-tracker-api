<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel;

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
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $sortKey = $sort ? $sort->cacheKey() : 'sort:default';
        $cacheKey = "subscriptions:paginated:page:{$page}:per_page:{$perPage}:{$searchKey}:{$sortKey}";

        $this->logger()->debug('Finding subscription with pagination', [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($page, $perPage, $search, $sort) {
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
                } else {
                    $query->orderBy('created_at', 'desc');
                }

                $paginator = $query->paginate(
                    perPage: $perPage,
                    page: $page
                );

                // Converte para DTOs
                $items = $paginator->getCollection()->map(function ($model) {
                    return SubscriptionDTO::fromDatabase($model);
                })->all();

                return new LengthAwarePaginator(
                    items: $items,
                    total: $paginator->total(),
                    perPage: $paginator->perPage(),
                    currentPage: $paginator->currentPage(),
                    options: [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );
            }
        );
    }
}
