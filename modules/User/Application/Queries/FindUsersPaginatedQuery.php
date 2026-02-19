<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\User\Application\DTOs\UserPaginatedDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

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

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    protected function cacheTags(): array
    {
        return ['users'];
    }

    public function execute(
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): UserPaginatedDTO {
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $sortKey = $sort ? $sort->cacheKey() : 'sort:default';
        $cacheKey = "users:paginated:page:{$page}:per_page:{$perPage}:{$searchKey}:{$sortKey}";

        $this->logger()->debug('Finding users with pagination', [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($page, $perPage) {
                $paginationData = $this->userRepository->findPaginated($page, $perPage);

                // Converte entidades para DTOs
                $usersDTO = array_map(
                    fn ($user) => UserDTO::fromEntity($user),
                    $paginationData['data']
                );

                return UserPaginatedDTO::fromArray([
                    'data' => $usersDTO,
                    'total' => $paginationData['total'],
                    'per_page' => $paginationData['per_page'],
                    'current_page' => $paginationData['current_page'],
                    'last_page' => $paginationData['last_page'],
                ]);
            }
        );
    }
}
