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
        $this->logger()->debug(message: 'Finding users with pagination', context: [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        $searchColumns = $search?->columns;
        $searchTerm = $search?->term;
        
        $sorts = $sort?->sorts;

        $paginationData = $this->userRepository->findPaginated(
            page: $page,
            perPage: $perPage,
            searchColumns: $searchColumns,
            searchTerm: $searchTerm,
            sorts: $sorts
        );

        // Converte entidades para DTOs
        $usersDTO = array_map(
            fn ($user) => UserDTO::fromEntity($user),
            $paginationData['users']
        );

        return new UserPaginatedDTO(
            users: $usersDTO,
            total: $paginationData['total'],
            perPage: $paginationData['per_page'],
            currentPage: $paginationData['current_page'],
            lastPage: $paginationData['last_page'],
        );
    }
}
