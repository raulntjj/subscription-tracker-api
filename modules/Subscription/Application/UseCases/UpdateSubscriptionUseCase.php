<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Application\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class UpdateSubscriptionUseCase
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository
    ) {
    }

    /**
     * Atualiza todos os campos do subscription (PUT)
     * Todos os campos são obrigatórios
     */
    public function execute(string $id, UpdateSubscriptionDTO $dto): SubscriptionDTO
    {
        $this->logger()->info('Updating subscription', [
            'subscription_id' => $id,
        ]);

        try {
            $uuid = Uuid::fromString($id);

            $entity = $this->repository->findById($uuid);

            if ($entity === null) {
                throw new \InvalidArgumentException("Subscription not found with id: {$id}");
            }

            if ($dto->name === null) {
                throw new \InvalidArgumentException('All fields (name) are required for full update');
            }

            $entity->changeName($dto->name);

            $this->repository->update($entity);

            $this->logger()->event('SubscriptionUpdated', [
                'subscription_id' => $id,
            ]);

            $this->logger()->audit(
                action: 'update',
                entityType: 'Subscription',
                entityId: $id,
                context: [
                    'name' => $entity->name(),
                ]
            );

            return SubscriptionDTO::fromEntity($entity);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to update subscription', [
                'subscription_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
