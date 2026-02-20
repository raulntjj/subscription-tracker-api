<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

final readonly class SubscriptionPaginatedDTO
{
    /**
     * @param SubscriptionDTO[] $subscriptions
     */
    public function __construct(
        public array $subscriptions,
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ) {
    }

    public function toArray(): array
    {
        return [
            'subscriptions' => array_map(fn (SubscriptionDTO $subscription) => $subscription->toArray(), $this->subscriptions),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
        ];
    }
}
