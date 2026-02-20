<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Application\UseCases;

use Ramsey\Uuid\Uuid;
use RuntimeException;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Application\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Application\UseCases\CreateSubscriptionUseCase;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

final class CreateSubscriptionUseCaseTest extends SubscriptionTestCase
{
    private MockObject&SubscriptionRepositoryInterface $subscriptionRepository;
    private CreateSubscriptionUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->useCase = new CreateSubscriptionUseCase($this->subscriptionRepository);
    }

    public function test_creates_subscription_with_valid_data(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Subscription::class));

        $dto = new CreateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $result = $this->useCase->execute($dto);

        $this->assertInstanceOf(SubscriptionDTO::class, $result);
        $this->assertEquals('Netflix', $result->name);
        $this->assertEquals(4990, $result->price);
        $this->assertEquals('BRL', $result->currency);
        $this->assertEquals('monthly', $result->billingCycle);
        $this->assertEquals('Streaming', $result->category);
        $this->assertEquals('active', $result->status);
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->createdAt);
    }

    public function test_creates_subscription_with_yearly_billing_cycle(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save');

        $dto = new CreateSubscriptionDTO(
            name: 'Adobe Creative Cloud',
            price: 59880,
            currency: 'BRL',
            billingCycle: 'yearly',
            nextBillingDate: '2027-02-20',
            category: 'Software',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals('yearly', $result->billingCycle);
        $this->assertEquals(59880, $result->price);
    }

    public function test_creates_subscription_with_different_currencies(): void
    {
        $this->subscriptionRepository
            ->expects($this->exactly(3))
            ->method('save');

        $currencies = ['BRL', 'USD', 'EUR'];

        foreach ($currencies as $currency) {
            $dto = new CreateSubscriptionDTO(
                name: 'Test Subscription',
                price: 1000,
                currency: $currency,
                billingCycle: 'monthly',
                nextBillingDate: '2026-03-01',
                category: 'Test',
                status: 'active',
                userId: Uuid::uuid4()->toString(),
            );

            $result = $this->useCase->execute($dto);

            $this->assertEquals($currency, $result->currency);
        }
    }

    public function test_creates_subscription_with_valid_uuid(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save');

        $dto = new CreateSubscriptionDTO(
            name: 'Spotify',
            price: 2190,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Music',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $result = $this->useCase->execute($dto);

        $this->assertTrue(Uuid::isValid($result->id));
        $this->assertTrue(Uuid::isValid($result->userId));
    }

    public function test_throws_exception_for_past_billing_date(): void
    {
        $this->subscriptionRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(InvalidArgumentException::class);

        $dto = new CreateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-01-01',
            category: 'Streaming',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $this->useCase->execute($dto);
    }

    public function test_passes_correct_entity_to_repository(): void
    {
        $userId = Uuid::uuid4()->toString();

        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Subscription $subscription) use ($userId): bool {
                return $subscription->name() === 'Netflix'
                    && $subscription->price() === 4990
                    && $subscription->currency()->value === 'BRL'
                    && $subscription->billingCycle()->value === 'monthly'
                    && $subscription->category() === 'Streaming'
                    && $subscription->status()->value === 'active'
                    && $subscription->userId()->toString() === $userId
                    && $subscription->nextBillingDate()->format('Y-m-d') === '2026-03-01'
                    && $subscription->createdAt() instanceof DateTimeImmutable;
            }));

        $dto = new CreateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
            userId: $userId,
        );

        $this->useCase->execute($dto);
    }

    public function test_rethrows_repository_exception(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new RuntimeException('Database error'));

        $this->expectException(RuntimeException::class);

        $dto = new CreateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $this->useCase->execute($dto);
    }

    public function test_creates_subscription_with_zero_price(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save');

        $dto = new CreateSubscriptionDTO(
            name: 'Free Tier',
            price: 0,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Free',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals(0, $result->price);
    }

    public function test_creates_subscription_with_future_billing_date(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save');

        $futureDate = (new DateTimeImmutable('+1 year'))->format('Y-m-d');

        $dto = new CreateSubscriptionDTO(
            name: 'Long Term',
            price: 10000,
            currency: 'BRL',
            billingCycle: 'yearly',
            nextBillingDate: $futureDate,
            category: 'Test',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $result = $this->useCase->execute($dto);

        $this->assertEquals($futureDate, $result->nextBillingDate);
    }

    public function test_creates_subscription_with_different_categories(): void
    {
        $this->subscriptionRepository
            ->expects($this->exactly(3))
            ->method('save');

        $categories = ['Streaming', 'Software', 'Cloud Storage'];

        foreach ($categories as $category) {
            $dto = new CreateSubscriptionDTO(
                name: 'Test',
                price: 1000,
                currency: 'BRL',
                billingCycle: 'monthly',
                nextBillingDate: '2026-03-01',
                category: $category,
                status: 'active',
                userId: Uuid::uuid4()->toString(),
            );

            $result = $this->useCase->execute($dto);

            $this->assertEquals($category, $result->category);
        }
    }

    public function test_stores_price_in_cents(): void
    {
        $this->subscriptionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Subscription $subscription): bool {
                return $subscription->price() === 4990; // R$ 49.90 em centavos
            }));

        $dto = new CreateSubscriptionDTO(
            name: 'Netflix',
            price: 4990,
            currency: 'BRL',
            billingCycle: 'monthly',
            nextBillingDate: '2026-03-01',
            category: 'Streaming',
            status: 'active',
            userId: Uuid::uuid4()->toString(),
        );

        $this->useCase->execute($dto);
    }
}
