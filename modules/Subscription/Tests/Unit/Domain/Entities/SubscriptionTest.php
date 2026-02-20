<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Domain\Entities;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Enums\CurrencyEnum;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;

final class SubscriptionTest extends SubscriptionTestCase
{
    private UuidInterface $id;
    private UuidInterface $userId;
    private DateTimeImmutable $nextBillingDate;
    private DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = Uuid::uuid4();
        $this->userId = Uuid::uuid4();
        $this->nextBillingDate = new DateTimeImmutable('2026-03-01');
        $this->createdAt = new DateTimeImmutable('2026-02-20 12:00:00');
    }

    public function test_creates_subscription_with_required_fields(): void
    {
        $subscription = new Subscription(
            id: $this->id,
            name: 'Netflix',
            price: 4990, // R$ 49,90
            currency: CurrencyEnum::BRL,
            billingCycle: BillingCycleEnum::MONTHLY,
            nextBillingDate: $this->nextBillingDate,
            category: 'Streaming',
            status: SubscriptionStatusEnum::ACTIVE,
            userId: $this->userId,
            createdAt: $this->createdAt,
        );

        $this->assertEquals($this->id, $subscription->id());
        $this->assertEquals('Netflix', $subscription->name());
        $this->assertEquals(4990, $subscription->price());
        $this->assertEquals(CurrencyEnum::BRL, $subscription->currency());
        $this->assertEquals(BillingCycleEnum::MONTHLY, $subscription->billingCycle());
        $this->assertEquals($this->nextBillingDate, $subscription->nextBillingDate());
        $this->assertEquals('Streaming', $subscription->category());
        $this->assertEquals(SubscriptionStatusEnum::ACTIVE, $subscription->status());
        $this->assertEquals($this->userId, $subscription->userId());
        $this->assertEquals($this->createdAt, $subscription->createdAt());
        $this->assertNull($subscription->updatedAt());
    }

    public function test_creates_subscription_with_updated_at(): void
    {
        $updatedAt = new DateTimeImmutable('2026-02-21 10:00:00');

        $subscription = new Subscription(
            id: $this->id,
            name: 'Spotify',
            price: 2190,
            currency: CurrencyEnum::BRL,
            billingCycle: BillingCycleEnum::MONTHLY,
            nextBillingDate: $this->nextBillingDate,
            category: 'Music',
            status: SubscriptionStatusEnum::ACTIVE,
            userId: $this->userId,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertEquals($updatedAt, $subscription->updatedAt());
    }

    public function test_id_returns_uuid_interface(): void
    {
        $subscription = $this->createSubscription();

        $this->assertInstanceOf(UuidInterface::class, $subscription->id());
    }

    public function test_throws_exception_for_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Subscription(
            id: $this->id,
            name: 'Netflix',
            price: -100,
            currency: CurrencyEnum::BRL,
            billingCycle: BillingCycleEnum::MONTHLY,
            nextBillingDate: $this->nextBillingDate,
            category: 'Streaming',
            status: SubscriptionStatusEnum::ACTIVE,
            userId: $this->userId,
            createdAt: $this->createdAt,
        );
    }

    public function test_throws_exception_for_past_billing_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $pastDate = new DateTimeImmutable('2026-01-01');

        new Subscription(
            id: $this->id,
            name: 'Netflix',
            price: 4990,
            currency: CurrencyEnum::BRL,
            billingCycle: BillingCycleEnum::MONTHLY,
            nextBillingDate: $pastDate,
            category: 'Streaming',
            status: SubscriptionStatusEnum::ACTIVE,
            userId: $this->userId,
            createdAt: $this->createdAt,
        );
    }

    public function test_change_name_updates_name_and_timestamp(): void
    {
        $subscription = $this->createSubscription();
        $originalUpdatedAt = $subscription->updatedAt();

        $subscription->changeName('Netflix Premium');

        $this->assertEquals('Netflix Premium', $subscription->name());
        $this->assertNotEquals($originalUpdatedAt, $subscription->updatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $subscription->updatedAt());
    }

    public function test_change_price_updates_price_and_timestamp(): void
    {
        $subscription = $this->createSubscription();

        $subscription->changePrice(5990);

        $this->assertEquals(5990, $subscription->price());
        $this->assertInstanceOf(DateTimeImmutable::class, $subscription->updatedAt());
    }

    public function test_change_price_throws_exception_for_negative_value(): void
    {
        $subscription = $this->createSubscription();

        $this->expectException(InvalidArgumentException::class);

        $subscription->changePrice(-100);
    }

    public function test_change_billing_cycle_updates_cycle_and_timestamp(): void
    {
        $subscription = $this->createSubscription();

        $subscription->changeBillingCycle(BillingCycleEnum::YEARLY);

        $this->assertEquals(BillingCycleEnum::YEARLY, $subscription->billingCycle());
        $this->assertInstanceOf(DateTimeImmutable::class, $subscription->updatedAt());
    }

    public function test_change_category_updates_category_and_timestamp(): void
    {
        $subscription = $this->createSubscription();

        $subscription->changeCategory('Entertainment');

        $this->assertEquals('Entertainment', $subscription->category());
        $this->assertInstanceOf(DateTimeImmutable::class, $subscription->updatedAt());
    }

    public function test_activate_changes_status_to_active(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatusEnum::PAUSED);

        $subscription->activate();

        $this->assertEquals(SubscriptionStatusEnum::ACTIVE, $subscription->status());
        $this->assertTrue($subscription->isActive());
    }

    public function test_pause_changes_status_to_paused(): void
    {
        $subscription = $this->createSubscription();

        $subscription->pause();

        $this->assertEquals(SubscriptionStatusEnum::PAUSED, $subscription->status());
        $this->assertTrue($subscription->isPaused());
    }

    public function test_cancel_changes_status_to_cancelled(): void
    {
        $subscription = $this->createSubscription();

        $subscription->cancel();

        $this->assertEquals(SubscriptionStatusEnum::CANCELLED, $subscription->status());
        $this->assertTrue($subscription->isCancelled());
    }

    public function test_update_next_billing_date_updates_date_and_timestamp(): void
    {
        $subscription = $this->createSubscription();
        $newDate = new DateTimeImmutable('2026-04-01');

        $subscription->updateNextBillingDate($newDate);

        $this->assertEquals($newDate, $subscription->nextBillingDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $subscription->updatedAt());
    }

    public function test_update_next_billing_date_throws_exception_for_past_date(): void
    {
        $subscription = $this->createSubscription();
        $pastDate = new DateTimeImmutable('2026-01-01');

        $this->expectException(InvalidArgumentException::class);

        $subscription->updateNextBillingDate($pastDate);
    }

    public function test_calculate_next_billing_date_for_monthly_cycle(): void
    {
        $currentDate = new DateTimeImmutable('2026-03-01');
        $subscription = $this->createSubscription(
            billingCycle: BillingCycleEnum::MONTHLY,
            nextBillingDate: $currentDate,
        );

        $nextDate = $subscription->calculateNextBillingDate();

        $this->assertEquals('2026-04-01', $nextDate->format('Y-m-d'));
    }

    public function test_calculate_next_billing_date_for_yearly_cycle(): void
    {
        $currentDate = new DateTimeImmutable('2026-03-01');
        $subscription = $this->createSubscription(
            billingCycle: BillingCycleEnum::YEARLY,
            nextBillingDate: $currentDate,
        );

        $nextDate = $subscription->calculateNextBillingDate();

        $this->assertEquals('2027-03-01', $nextDate->format('Y-m-d'));
    }

    public function test_normalized_monthly_price_for_monthly_subscription(): void
    {
        $subscription = $this->createSubscription(
            price: 4990,
            billingCycle: BillingCycleEnum::MONTHLY,
        );

        $this->assertEquals(4990, $subscription->normalizedMonthlyPrice());
    }

    public function test_normalized_monthly_price_for_yearly_subscription(): void
    {
        $subscription = $this->createSubscription(
            price: 59880, // R$ 598,80 por ano
            billingCycle: BillingCycleEnum::YEARLY,
        );

        // Deve dividir por 12: 59880 / 12 = 4990
        $this->assertEquals(4990, $subscription->normalizedMonthlyPrice());
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatusEnum::ACTIVE);

        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isPaused());
        $this->assertFalse($subscription->isCancelled());
    }

    public function test_is_paused_returns_true_for_paused_status(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatusEnum::PAUSED);

        $this->assertFalse($subscription->isActive());
        $this->assertTrue($subscription->isPaused());
        $this->assertFalse($subscription->isCancelled());
    }

    public function test_is_cancelled_returns_true_for_cancelled_status(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatusEnum::CANCELLED);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($subscription->isPaused());
        $this->assertTrue($subscription->isCancelled());
    }

    public function test_is_due_for_billing_returns_true_when_date_matches_today(): void
    {
        $today = new DateTimeImmutable('today');
        $subscription = $this->createSubscription(
            status: SubscriptionStatusEnum::ACTIVE,
            nextBillingDate: $today,
        );

        $this->assertTrue($subscription->isDueForBilling());
    }

    public function test_is_due_for_billing_returns_false_when_date_is_future(): void
    {
        $futureDate = new DateTimeImmutable('+7 days');
        $subscription = $this->createSubscription(
            status: SubscriptionStatusEnum::ACTIVE,
            nextBillingDate: $futureDate,
        );

        $this->assertFalse($subscription->isDueForBilling());
    }

    public function test_is_due_for_billing_returns_false_when_not_active(): void
    {
        $today = new DateTimeImmutable('today');
        $subscription = $this->createSubscription(
            status: SubscriptionStatusEnum::PAUSED,
            nextBillingDate: $today,
        );

        $this->assertFalse($subscription->isDueForBilling());
    }

    public function test_accepts_different_currencies(): void
    {
        $subscriptionBRL = $this->createSubscription(currency: CurrencyEnum::BRL);
        $subscriptionUSD = $this->createSubscription(currency: CurrencyEnum::USD);
        $subscriptionEUR = $this->createSubscription(currency: CurrencyEnum::EUR);

        $this->assertEquals(CurrencyEnum::BRL, $subscriptionBRL->currency());
        $this->assertEquals(CurrencyEnum::USD, $subscriptionUSD->currency());
        $this->assertEquals(CurrencyEnum::EUR, $subscriptionEUR->currency());
    }

    private function createSubscription(
        ?string $name = 'Netflix',
        ?int $price = 4990,
        ?CurrencyEnum $currency = null,
        ?BillingCycleEnum $billingCycle = null,
        ?DateTimeImmutable $nextBillingDate = null,
        ?string $category = 'Streaming',
        ?SubscriptionStatusEnum $status = null,
        ?DateTimeImmutable $updatedAt = null,
    ): Subscription {
        return new Subscription(
            id: $this->id,
            name: $name,
            price: $price,
            currency: $currency ?? CurrencyEnum::BRL,
            billingCycle: $billingCycle ?? BillingCycleEnum::MONTHLY,
            nextBillingDate: $nextBillingDate ?? $this->nextBillingDate,
            category: $category,
            status: $status ?? SubscriptionStatusEnum::ACTIVE,
            userId: $this->userId,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
        );
    }
}
