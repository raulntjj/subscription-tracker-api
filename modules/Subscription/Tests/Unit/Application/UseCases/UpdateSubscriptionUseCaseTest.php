<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Application\UseCases;

use Ramsey\Uuid\Uuid;
use RuntimeException;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Subscription\Domain\Enums\CurrencyEnum;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;
use Modules\Subscription\Application\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Application\UseCases\UpdateSubscriptionUseCase;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

final class UpdateSubscriptionUseCaseTest extends SubscriptionTestCase
{
    private MockObject&SubscriptionRepositoryInterface $subscriptionRepository;
    private UpdateSubscriptionUseCase $useCase;
    private UuidInterface $subscriptionId;
    private UuidInterface $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->useCase = new UpdateSubscriptionUseCase($this->subscriptionRepository);
        $this->subscriptionId = Uuid::uuid4();
        $this->userId = Uuid::uuid4();
    }

    public function test_updates_subscription_with_valid_data(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->subscriptionId)
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->isInstanceOf(Subscription::class));

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix Premium',
            price: 5990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-04-01',
            category: 'Streaming',
            status: 'active',
        );

        $result = $this->useCase->execute($this->subscriptionId->toString(), $dto);

        $this->assertInstanceOf(SubscriptionDTO::class, $result);
        $this->assertEquals('Netflix Premium', $result->name);
        $this->assertEquals(5990, $result->price);
    }

    public function test_updates_all_subscription_fields(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Subscription $subscription): bool {
                return $subscription->name() === 'Updated Name'
                    && $subscription->price() === 9990
                    && $subscription->billingCycle() === BillingCycleEnum::YEARLY
                    && $subscription->category() === 'Updated Category'
                    && $subscription->status() === SubscriptionStatusEnum::PAUSED;
            }));

        $dto = new UpdateSubscriptionDTO(
            name: 'Updated Name',
            price: 9990,
            currency: 'BRL',
            billingCycle: 'yearly',
            nextBillingDate: '2027-02-20',
            category: 'Updated Category',
            status: 'paused',
        );

        $this->useCase->execute($this->subscriptionId->toString(), $dto);
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
            ->method('update');

        $this->expectException(InvalidArgumentException::class);

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
        );

        $this->useCase->execute($this->subscriptionId->toString(), $dto);
    }

    public function test_throws_exception_for_past_billing_date(): void
    {
        $this->subscriptionRepository
            ->expects($this->never())
            ->method('findById');

        $this->subscriptionRepository
            ->expects($this->never())
            ->method('update');

        $this->expectException(InvalidArgumentException::class);

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-01-01',
            category: 'Streaming',
            status: 'active',
        );

        $this->useCase->execute($this->subscriptionId->toString(), $dto);
    }

    public function test_updates_subscription_status_to_active(): void
    {
        $existingSubscription = $this->createExistingSubscription(status: SubscriptionStatusEnum::PAUSED);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update');

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
        );

        $result = $this->useCase->execute($this->subscriptionId->toString(), $dto);

        $this->assertEquals('active', $result->status);
    }

    public function test_updates_subscription_status_to_paused(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update');

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'paused',
        );

        $result = $this->useCase->execute($this->subscriptionId->toString(), $dto);

        $this->assertEquals('paused', $result->status);
    }

    public function test_updates_subscription_status_to_cancelled(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update');

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'cancelled',
        );

        $result = $this->useCase->execute($this->subscriptionId->toString(), $dto);

        $this->assertEquals('cancelled', $result->status);
    }

    public function test_updates_billing_cycle_from_monthly_to_yearly(): void
    {
        $existingSubscription = $this->createExistingSubscription(billingCycle: BillingCycleEnum::MONTHLY);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update');

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 59880,
            currency: 'BRL',
            billingCycle: 'yearly',
            nextBillingDate: '2027-02-20',
            category: 'Streaming',
            status: 'active',
        );

        $result = $this->useCase->execute($this->subscriptionId->toString(), $dto);

        $this->assertEquals('yearly', $result->billingCycle);
    }

    public function test_updates_next_billing_date(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Subscription $subscription): bool {
                return $subscription->nextBillingDate()->format('Y-m-d') === '2026-05-15';
            }));

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-05-15',
            category: 'Streaming',
            status: 'active',
        );

        $this->useCase->execute($this->subscriptionId->toString(), $dto);
    }

    public function test_updates_category(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update');

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Entertainment',
            status: 'active',
        );

        $result = $this->useCase->execute($this->subscriptionId->toString(), $dto);

        $this->assertEquals('Entertainment', $result->category);
    }

    public function test_rethrows_repository_exception(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new RuntimeException('Database error'));

        $this->expectException(RuntimeException::class);

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
        );

        $this->useCase->execute($this->subscriptionId->toString(), $dto);
    }

    public function test_updated_at_timestamp_is_set(): void
    {
        $existingSubscription = $this->createExistingSubscription();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingSubscription);

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Subscription $subscription): bool {
                return $subscription->updatedAt() instanceof DateTimeImmutable;
            }));

        $dto = new UpdateSubscriptionDTO(
            name: 'Netflix Premium',
            price: 5990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
        );

        $this->useCase->execute($this->subscriptionId->toString(), $dto);
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
