<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Application\DTOs\SubscriptionCursorPaginatedDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

/**
 * Query para buscar subscription com cursor pagination (mobile)
 * Suporta busca e ordenação dinâmica
 */
final readonly class FindSubscriptionsCursorPaginatedQuery
{
    use Loggable;

    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private SubscriptionRepositoryInterface $repository
    ) {
    }

    public function execute(
        ?string $cursor = null,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): SubscriptionCursorPaginatedDTO {
        $this->logger()->debug('Finding subscription with cursor pagination', [
            'cursor' => $cursor,
            'per_page' => $perPage,
            'search' => $search?->term,
            'sort' => $sort?->sorts,
        ]);

        // Extrai parâmetros de busca
        $searchColumns = null;
        $searchTerm = null;
        if ($search !== null && $search->hasSearch()) {
            $searchColumns = $search->columns;
            $searchTerm = $search->term;
        }

        // Extrai parâmetros de ordenação
        $sorts = null;
        if ($sort !== null && $sort->hasSorts()) {
            $sorts = $sort->sorts;
        }

        $result = $this->repository->findCursorPaginated(
            $perPage,
            $cursor,
            $searchColumns,
            $searchTerm,
            $sorts
        );

        // Converte entidades para DTOs
        $dtos = array_map(
            fn ($entity) => SubscriptionDTO::fromEntity($entity),
            $result['data']
        );

        return new SubscriptionCursorPaginatedDTO(
            data: $dtos,
            nextCursor: $result['next_cursor'],
            prevCursor: $result['prev_cursor'],
        );
    }
}

