<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

/**
 * Query para buscar um subscription por ID
 */
final readonly class FindSubscriptionByIdQuery
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository,
    ) {
    }

    public function execute(string $id): ?SubscriptionDTO
    {
        $this->logger()->debug('Finding subscription by ID', [
            'subscription_id' => $id,
        ]);

        $subscription = $this->repository->findById(Uuid::fromString($id));

        if ($subscription === null) {
            $this->logger()->debug('Subscription not found', [
                'subscription_id' => $id,
            ]);

            return null;
        }

        return SubscriptionDTO::fromEntity($subscription);
    }
}
