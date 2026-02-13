<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Application\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class PartialUpdateSubscriptionUseCase
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository
    ) {
    }

    /**
     * Atualiza parcialmente os campos do subscription (PATCH)
     * Apenas os campos preenchidos no DTO serão atualizados
     */
    public function execute(string $id, UpdateSubscriptionDTO $dto): SubscriptionDTO
    {
        $this->logger()->info('Patching subscription', [
            'subscription_id' => $id,
            'fields' => array_keys($dto->toArray()),
        ]);

        try {
            $uuid = Uuid::fromString($id);

            $entity = $this->repository->findById($uuid);

            if ($entity === null) {
                throw new \InvalidArgumentException("Subscription not found with id: {$id}");
            }

            if (!$dto->hasChanges()) {
                throw new \InvalidArgumentException('No fields provided for update');
            }

            if ($dto->name !== null) {
                $entity->changeName($dto->name);
            }

            $this->repository->update($entity);

            $this->logger()->event('SubscriptionPatched', [
                'subscription_id' => $id,
                'updated_fields' => array_keys($dto->toArray()),
            ]);

            $this->logger()->audit(
                action: 'patch',
                entityType: 'Subscription',
                entityId: $id,
                context: [
                    'updated_fields' => array_keys($dto->toArray()),
                    'name' => $entity->name(),
                ]
            );

            return SubscriptionDTO::fromEntity($entity);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to patch subscription', [
                'subscription_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
