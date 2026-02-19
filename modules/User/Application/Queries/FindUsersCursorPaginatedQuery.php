<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\User\Application\DTOs\UserCursorPaginatedDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar usuários com cursor pagination (mobile)
 * Suporta busca e ordenação dinâmica
 */
final readonly class FindUsersCursorPaginatedQuery
{
    use Loggable;
    use Cacheable;

    private const DEFAULT_PER_PAGE = 20;
    private const CACHE_TTL = 300; // 5 minutos

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    protected function cacheTags(): array
    {
        return ['users'];
    }

    public function execute(
        ?string $cursor = null,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): UserCursorPaginatedDTO {
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

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($cursor, $perPage) {
                $paginationData = $this->userRepository->findCursorPaginated($perPage, $cursor);

                // Converte entidades para DTOs
                $usersDTO = array_map(
                    fn ($user) => UserDTO::fromEntity($user),
                    $paginationData['data']
                );

                return UserCursorPaginatedDTO::fromArray([
                    'data' => $usersDTO,
                    'next_cursor' => $paginationData['next_cursor'],
                    'prev_cursor' => $paginationData['prev_cursor'],
                ]);
            }
        );
    }
}
