<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Modules\Subscription\Domain\Enums\CurrencyEnum;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;
use Modules\Subscription\Application\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

final readonly class CreateSubscriptionUseCase
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository,
    ) {
    }

    public function execute(CreateSubscriptionDTO $dto): SubscriptionDTO
    {
        $this->logger()->info('Creating new subscription', [
            'name' => $dto->name,
            'price' => $dto->price,
            'currency' => $dto->currency,
            'billing_cycle' => $dto->billingCycle,
            'category' => $dto->category,
        ]);

        try {
            if (!$dto->validateNextBillingDate()) {
                throw new \InvalidArgumentException('Next billing date must be in the future or today');
            }

            $entity = new Subscription(
                id: Uuid::uuid4(),
                name: $dto->name,
                price: $dto->price,
                currency: CurrencyEnum::from($dto->currency),
                billingCycle: BillingCycleEnum::from($dto->billingCycle),
                nextBillingDate: new DateTimeImmutable($dto->nextBillingDate),
                category: $dto->category,
                status: SubscriptionStatusEnum::from($dto->status),
                userId: Uuid::fromString($dto->userId),
                createdAt: new DateTimeImmutable(),
            );

            $this->repository->save($entity);

            $this->logger()->event('SubscriptionCreated', [
                'subscription_id' => $entity->id()->toString(),
                'user_id' => $dto->userId,
            ]);

            $this->logger()->audit(
                action: 'create',
                entityType: 'Subscription',
                entityId: $entity->id()->toString(),
                context: [
                    'name' => $entity->name(),
                    'price' => $entity->price(),
                    'currency' => $entity->currency()->value,
                    'billing_cycle' => $entity->billingCycle()->value,
                    'category' => $entity->category(),
                ],
            );

            return SubscriptionDTO::fromEntity($entity);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to create subscription', [
                'name' => $dto->name,
            ], $e);

            throw $e;
        }
    }
}
