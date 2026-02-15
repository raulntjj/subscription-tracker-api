<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar opções de subscription (sem paginação)
 * Ideal para popular selects e autocompletes
 */
final readonly class FindSubscriptionOptionsQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 300; // 5 minutos

    protected function cacheTags(): array
    {
        return ['subscriptions'];
    }

    /**
     * @return array<SubscriptionDTO>
     */
    public function execute(?SearchDTO $search = null): array
    {
        $searchKey = $search ? $search->cacheKey() : 'search:none';
        $cacheKey = "subscriptions:options:{$searchKey}";

        $this->logger()->debug('Finding subscription options', [
            'search' => $search?->term,
        ]);

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($search) {
                $query = SubscriptionModel::query()
                    ->orderBy('name', 'asc');

                // Aplica busca
                if ($search !== null && $search->hasSearch()) {
                    $query->where(function ($q) use ($search) {
                        foreach ($search->columns as $column) {
                            $q->orWhere($column, 'LIKE', "%{$search->term}%");
                        }
                    });
                }

                $data = $query->get();

                $items = $data->map(function ($item) {
                    return SubscriptionDTO::fromDatabase($item);
                })->all();

                return $items;
            }
        );
    }
}
