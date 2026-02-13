<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar usuários com paginação offset (web)
 * Suporta busca e ordenação dinâmica
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

        // Cache da paginação (apenas IDs e total)
        $result = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($page, $perPage, $search, $sort, $startTime) {
                $this->logger()->debug('Cache miss - fetching IDs from database');

                $baseQuery = DB::table('users');

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

                $userIds = $baseQuery
                    ->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->pluck('id')
                    ->all();

                $duration = microtime(true) - $startTime;

                $this->logger()->info('User IDs paginated retrieved', [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'search' => $search?->term,
                    'cache_hit' => false,
                    'duration_ms' => round($duration * 1000, 2),
                ]);

                return [
                    'ids' => $userIds,
                    'total' => $total,
                ];
            }
        );

        // Busca cada usuário do cache individual
        $users = [];
        foreach ($result['ids'] as $userId) {
            $userCacheKey = "user:{$userId}";

            $userData = $this->cache()->remember(
                $userCacheKey,
                3600, // 1 hora (mesmo TTL do FindUserByIdQuery)
                function () use ($userId) {
                    $userData = DB::table('users')
                        ->select(['id', 'name', 'surname', 'email', 'profile_path', 'created_at', 'updated_at'])
                        ->where('id', $userId)
                        ->first();

                    return $userData ? (array) $userData : null;
                }
            );

            if ($userData !== null) {
                $users[] = UserDTO::fromDatabase($userData);
            }
        }

        $duration = microtime(true) - $startTime;

        $this->logger()->info('Users paginated returned', [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $result['total'],
            'search' => $search?->term,
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return new LengthAwarePaginator(
            items: $users,
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
