<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Illuminate\Support\Facades\DB;
use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar usuários com cursor pagination (mobile)
 * Suporta busca e ordenação dinâmica
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

        $this->logger()->debug('Finding users with cursor pagination', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        $query = DB::table('users')
            ->select(['id', 'name', 'surname', 'email', 'profile_path', 'created_at', 'updated_at']);

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

        // Laravel automaticamente gerencia o cursor
        $paginator = $query->cursorPaginate(
            perPage: $perPage,
            cursor: $cursor ? \Illuminate\Pagination\Cursor::fromEncoded($cursor) : null
        );

        $usersData = [];
        foreach ($paginator->items() as $userData) {
            $userCacheKey = "user:{$userData->id}";

            // Tenta buscar do cache individual
            $cachedUserData = $this->cache()->remember(
                $userCacheKey,
                3600,
                function () use ($userData) {
                    return UserDTO::fromDatabase($userData)->toArray();
                }
            );

            $usersData[] = $cachedUserData;
        }

        $duration = microtime(true) - $startTime;

        $this->logger()->info('Users cursor paginated retrieved', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'has_more' => $paginator->hasMorePages(),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return [
            'users' => $usersData,
            'pagination' => [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'has_more' => $paginator->hasMorePages(),
                'per_page' => $paginator->perPage(),
            ],
        ];
    }
}
