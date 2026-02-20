<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

final readonly class SubscriptionOptionsDTO
{
    /**
     * @param array<array{id: string, name: string}> $options
     */
    public function __construct(
        public array $options,
    ) {
    }

    public function toArray(): array
    {
        return [
            'options' => $this->options,
        ];
    }
}
