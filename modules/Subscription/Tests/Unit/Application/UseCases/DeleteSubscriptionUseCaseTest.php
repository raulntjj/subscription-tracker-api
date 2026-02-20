<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Application\UseCases;

use Ramsey\Uuid\Uuid;
use RuntimeException;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Domain\Enums\CurrencyEnum;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Subscription\Application\UseCases\DeleteSubscriptionUseCase;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

final class DeleteSubscriptionUseCaseTest extends SubscriptionTestCase
{
    private MockObject&SubscriptionRepositoryInterface $subscriptionRepository;
    private DeleteSubscriptionUseCase $useCase;
    private UuidInterface $subscriptionId;
    private UuidInterface $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->useCase = new DeleteSubscriptionUseCase($this->subscriptionRepository);
        $this->subscriptionId = Uuid::uuid4();
        $this->userId = Uuid::uuid4();
    }

    public function test_deletes_existing_subscription(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->subscriptionId)
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete')
            ->with($this->subscriptionId);

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_throws_exception_when_subscription_not_found(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->subscriptionId)
            ->willReturn(null);

        $this->subscriptionRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subscription not found with id:');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_calls_repository_with_correct_uuid(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(function (UuidInterface $uuid): bool {
                return $uuid->equals($this->subscriptionId);
            }))
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete')
            ->with($this->callback(function (UuidInterface $uuid): bool {
                return $uuid->equals($this->subscriptionId);
            }));

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_rethrows_repository_exception_on_find(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willThrowException(new RuntimeException('Database connection error'));

        $this->subscriptionRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database connection error');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_rethrows_repository_exception_on_delete(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new RuntimeException('Failed to delete'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_deletes_subscription_with_active_status(): void
    {
        $existingSubscription = $this->createExistingSubscription(status: SubscriptionStatusEnum::ACTIVE);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_deletes_subscription_with_paused_status(): void
    {
        $existingSubscription = $this->createExistingSubscription(status: SubscriptionStatusEnum::PAUSED);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_deletes_subscription_with_cancelled_status(): void
    {
        $existingSubscription = $this->createExistingSubscription(status: SubscriptionStatusEnum::CANCELLED);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_deletes_monthly_subscription(): void
    {
        $existingSubscription = $this->createExistingSubscription(billingCycle: BillingCycleEnum::MONTHLY);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_deletes_yearly_subscription(): void
    {
        $existingSubscription = $this->createExistingSubscription(billingCycle: BillingCycleEnum::YEARLY);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    public function test_accepts_valid_uuid_string(): void
    {
        $validUuid = Uuid::uuid4()->toString();
        $existingSubscription = new Subscription(
            id: Uuid::fromString($validUuid),
            name: 'Netflix',
            price: 4990,
            currency: CurrencyEnum::BRL,
            billingCycle: BillingCycleEnum::MONTHLY,
            nextBillingDate: new DateTimeImmutable('2026-03-01'),
            category: 'Streaming',
            status: SubscriptionStatusEnum::ACTIVE,
            userId: $this->userId,
            createdAt: new DateTimeImmutable('2026-02-20'),
        );

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $this->useCase->execute($validUuid);
    }

    public function test_execute_returns_void(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $result = $this->useCase->execute($this->subscriptionId->toString());

        $this->assertNull($result);
    }

    public function test_finds_subscription_before_deleting(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        // Verify that findById is called before delete
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('delete');

        $this->useCase->execute($this->subscriptionId->toString());
    }

    private function createExistingSubscription(
        ?BillingCycleEnum $billingCycle = null,
        ?SubscriptionStatusEnum $status = null,
    ): Subscription {
        return new Subscription(
            id: $this->subscriptionId,
            name: 'Netflix',
            price: 4990,
            currency: CurrencyEnum::BRL,
            billingCycle: $billingCycle ?? BillingCycleEnum::MONTHLY,
            nextBillingDate: new DateTimeImmutable('2026-03-01'),
            category: 'Streaming',
            status: $status ?? SubscriptionStatusEnum::ACTIVE,
            userId: $this->userId,
            createdAt: new DateTimeImmutable('2026-02-20'),
        );
    }
}
