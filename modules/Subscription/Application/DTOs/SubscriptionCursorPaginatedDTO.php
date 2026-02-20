<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

final readonly class SubscriptionCursorPaginatedDTO
{
    /**
     * @param SubscriptionDTO[] $data
     */
    public function __construct(
        public array $data,
        public ?string $nextCursor,
        public ?string $prevCursor,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(fn (SubscriptionDTO $subscription) => $subscription->toArray(), $this->data),
            'next_cursor' => $this->nextCursor,
            'prev_cursor' => $this->prevCursor,
        ];
    }
}
