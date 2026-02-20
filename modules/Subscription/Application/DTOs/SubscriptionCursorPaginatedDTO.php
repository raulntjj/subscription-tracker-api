<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

final readonly class SubscriptionCursorPaginatedDTO
{
    /**
     * @param SubscriptionDTO[] $subscriptions
     */
    public function __construct(
        public array $subscriptions,
        public ?string $nextCursor,
        public ?string $prevCursor,
    ) {
    }

    public function toArray(): array
    {
        return [
            'subscriptions' => array_map(fn (SubscriptionDTO $subscription) => $subscription->toArray(), $this->subscriptions),
            'next_cursor' => $this->nextCursor,
            'prev_cursor' => $this->prevCursor,
        ];
    }
}
