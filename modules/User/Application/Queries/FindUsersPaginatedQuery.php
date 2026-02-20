<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\User\Application\DTOs\UserPaginatedDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar usuários com paginação offset (web)
 * Suporta busca e ordenação dinâmica
 */
final readonly class FindUsersPaginatedQuery
{
    use Loggable;

    private const DEFAULT_PER_PAGE = 15;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function execute(
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): UserPaginatedDTO {
        $this->logger()->debug('Finding users with pagination', [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

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
}
