<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Throwable;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;
use Modules\Subscription\Application\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

final readonly class UpdateSubscriptionUseCase
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository,
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
            // Valida a data de próximo faturamento
            if (!$dto->validateNextBillingDate()) {
                throw new InvalidArgumentException('Next billing date must be in the future or today');
            }

            $uuid = Uuid::fromString($id);

            $entity = $this->repository->findById($uuid);

            if ($entity === null) {
                throw new InvalidArgumentException("Subscription not found with id: {$id}");
            }

            // Atualiza todos os campos
            $entity->changeName($dto->name);
            $entity->changePrice($dto->price);
            $entity->changeBillingCycle(
                BillingCycleEnum::from($dto->billingCycle),
            );
            $entity->changeCategory($dto->category);
            $entity->updateNextBillingDate(new DateTimeImmutable($dto->nextBillingDate));

            // Atualiza status
            $status = SubscriptionStatusEnum::from($dto->status);
            match ($status) {
                SubscriptionStatusEnum::ACTIVE => $entity->activate(),
                SubscriptionStatusEnum::PAUSED => $entity->pause(),
                SubscriptionStatusEnum::CANCELLED => $entity->cancel(),
            };

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
                    'price' => $entity->price(),
                    'status' => $entity->status()->value,
                ],
            );

            return SubscriptionDTO::fromEntity($entity);
        } catch (Throwable $e) {
            $this->logger()->error('Failed to update subscription', [
                'subscription_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
