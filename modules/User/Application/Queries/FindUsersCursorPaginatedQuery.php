<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\User\Application\DTOs\UserCursorPaginatedDTO;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

/**
 * Query para buscar usuários com cursor pagination (mobile)
 * Suporta busca e ordenação dinâmica
 */
final readonly class FindUsersCursorPaginatedQuery
{
    use Loggable;

    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function execute(
        ?string $cursor = null,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): UserCursorPaginatedDTO {
        $this->logger()->debug('Finding users with cursor pagination', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

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
}
