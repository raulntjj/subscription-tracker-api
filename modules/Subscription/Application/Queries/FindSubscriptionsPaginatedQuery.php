<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Application\DTOs\SubscriptionPaginatedDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

/**
 * Query para buscar subscription com paginação offset (web)
 * Suporta busca e ordenação dinâmica
 */
final readonly class FindSubscriptionsPaginatedQuery
{
    use Loggable;

    private const DEFAULT_PER_PAGE = 15;

    public function __construct(
        private SubscriptionRepositoryInterface $repository
    ) {
    }

    public function execute(
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?SearchDTO $search = null,
        ?SortDTO $sort = null,
    ): SubscriptionPaginatedDTO {
        $this->logger()->debug('Finding subscription with pagination', [
            'page' => $page,
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

        $result = $this->repository->findPaginated(
            $page,
            $perPage,
            $searchColumns,
            $searchTerm,
            $sorts
        );

        // Converte entidades para DTOs
        $dtos = array_map(
            fn ($entity) => SubscriptionDTO::fromEntity($entity),
            $result['data']
        );

        return new SubscriptionPaginatedDTO(
            subscriptions: $dtos,
            total: $result['total'],
            perPage: $result['per_page'],
            currentPage: $result['current_page'],
            lastPage: $result['last_page'],
        );
    }
}

