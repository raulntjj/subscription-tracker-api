<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Application\DTOs\SubscriptionOptionsDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

/**
 * Query para buscar opções de subscription (sem paginação)
 * Ideal para popular selects e autocompletes
 */
final readonly class FindSubscriptionOptionsQuery
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository,
    ) {
    }

    public function execute(?SearchDTO $search = null): SubscriptionOptionsDTO
    {
        $this->logger()->debug('Finding subscription options', [
            'search' => $search?->term,
        ]);

        // Extrai parâmetros de busca
        $searchColumns = null;
        $searchTerm = null;
        if ($search !== null && $search->hasSearch()) {
            $searchColumns = $search->columns;
            $searchTerm = $search->term;
        }

        $options = $this->repository->findOptions($searchColumns, $searchTerm);

        return new SubscriptionOptionsDTO(options: $options);
    }
}
