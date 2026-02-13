<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\User\Application\DTOs\UserDTO;
use Modules\User\Infrastructure\Persistence\Eloquent\UserModel;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar usuários com paginação offset (web)
 * Suporta busca e ordenação dinâmica
 * 
 * CQRS: Queries podem usar Eloquent Models diretamente para leitura
 * Benefícios: soft deletes automático, casts, scopes, relations
 */
final readonly class FindUsersPaginatedQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 300; // 5 minutos
    private const DEFAULT_PER_PAGE = 15;

    protected function cacheTags(): array
    {
        return ['users'];
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
        $cacheKey = "users:paginated:page:{$page}:per_page:{$perPage}:{$searchKey}:{$sortKey}";

        $this->logger()->debug('Finding users with pagination', [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        // Cache completo da paginação (dados + metadados)
        $result = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($page, $perPage, $search, $sort, $startTime) {
                $this->logger()->debug('Cache miss - fetching from database');

                // Usa Eloquent Model - soft deletes automático!
                $query = UserModel::select(['id', 'name', 'surname', 'email', 'profile_path', 'created_at', 'updated_at']);

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

                // UMA ÚNICA query com paginate() - resolve N+1
                $paginator = $query->paginate($perPage, ['*'], 'page', $page);

                // Converte para DTOs
                $users = $paginator->getCollection()
                    ->map(fn($model) => UserDTO::fromDatabase($model))
                    ->all();

                $duration = microtime(true) - $startTime;

                $this->logger()->info('Users paginated retrieved', [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $paginator->total(),
                    'search' => $search?->term,
                    'cache_hit' => false,
                    'duration_ms' => round($duration * 1000, 2),
                ]);

                return [
                    'items' => $users,
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ];
            }
        );

        $duration = microtime(true) - $startTime;

        $this->logger()->info('Users paginated returned from cache', [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $result['total'],
            'search' => $search?->term,
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return new LengthAwarePaginator(
            items: $result['items'],
            total: $result['total'],
            perPage: $result['per_page'],
            currentPage: $result['current_page'],
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
