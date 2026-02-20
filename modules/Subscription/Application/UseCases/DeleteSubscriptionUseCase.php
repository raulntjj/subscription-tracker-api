<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Throwable;
use Ramsey\Uuid\Uuid;
use InvalidArgumentException;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

final readonly class DeleteSubscriptionUseCase
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository,
    ) {
    }

    public function execute(string $id): void
    {
        $this->logger()->info('Deleting subscription', [
            'subscription_id' => $id,
        ]);

        try {
            $uuid = Uuid::fromString($id);

            $entity = $this->repository->findById($uuid);

            if ($entity === null) {
                throw new InvalidArgumentException("Subscription not found with id: {$id}");
            }

            $this->repository->delete($uuid);

            $this->logger()->event('SubscriptionDeleted', [
                'subscription_id' => $id,
            ]);

            $this->logger()->audit(
                action: 'delete',
                entityType: 'Subscription',
                entityId: $id,
                context: [
                    'name' => $entity->name(),
                ],
            );
        } catch (Throwable $e) {
            $this->logger()->error('Failed to delete subscription', [
                'subscription_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
