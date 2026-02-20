<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Domain\Entities;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Entities\BillingHistory;

final class BillingHistoryTest extends SubscriptionTestCase
{
    private UuidInterface $id;
    private UuidInterface $subscriptionId;
    private DateTimeImmutable $paidAt;
    private DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = Uuid::uuid4();
        $this->subscriptionId = Uuid::uuid4();
        $this->paidAt = new DateTimeImmutable('2026-02-15 10:00:00');
        $this->createdAt = new DateTimeImmutable('2026-02-15 10:05:00');
    }

    public function test_creates_billing_history_with_all_fields(): void
    {
        $billingHistory = new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: 4990, // R$ 49,90
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $this->assertEquals($this->id, $billingHistory->id());
        $this->assertEquals($this->subscriptionId, $billingHistory->subscriptionId());
        $this->assertEquals(4990, $billingHistory->amountPaid());
        $this->assertEquals($this->paidAt, $billingHistory->paidAt());
        $this->assertEquals($this->createdAt, $billingHistory->createdAt());
    }

    public function test_id_returns_uuid_interface(): void
    {
        $billingHistory = $this->createBillingHistory();

        $this->assertInstanceOf(UuidInterface::class, $billingHistory->id());
    }

    public function test_subscription_id_returns_uuid_interface(): void
    {
        $billingHistory = $this->createBillingHistory();

        $this->assertInstanceOf(UuidInterface::class, $billingHistory->subscriptionId());
    }

    public function test_paid_at_returns_datetime_immutable(): void
    {
        $billingHistory = $this->createBillingHistory();

        $this->assertInstanceOf(DateTimeImmutable::class, $billingHistory->paidAt());
    }

    public function test_created_at_returns_datetime_immutable(): void
    {
        $billingHistory = $this->createBillingHistory();

        $this->assertInstanceOf(DateTimeImmutable::class, $billingHistory->createdAt());
    }

    public function test_amount_paid_formatted_returns_correct_float_value(): void
    {
        $billingHistory = new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: 4990, // R$ 49,90
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $this->assertEquals(49.90, $billingHistory->amountPaidFormatted());
    }

    public function test_amount_paid_formatted_handles_zero(): void
    {
        $billingHistory = new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: 0,
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $this->assertEquals(0.0, $billingHistory->amountPaidFormatted());
    }

    public function test_amount_paid_formatted_handles_large_values(): void
    {
        $billingHistory = new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: 999999, // R$ 9.999,99
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $this->assertEquals(9999.99, $billingHistory->amountPaidFormatted());
    }

    public function test_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: -100,
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );
    }

    public function test_accepts_zero_amount(): void
    {
        $billingHistory = new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: 0,
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $this->assertEquals(0, $billingHistory->amountPaid());
    }

    public function test_stores_amount_in_cents(): void
    {
        $billingHistory = new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: 2990, // R$ 29,90
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $this->assertEquals(2990, $billingHistory->amountPaid());
        $this->assertEquals(29.90, $billingHistory->amountPaidFormatted());
    }

    public function test_handles_different_subscription_ids(): void
    {
        $subscriptionId1 = Uuid::uuid4();
        $subscriptionId2 = Uuid::uuid4();

        $billing1 = new BillingHistory(
            id: Uuid::uuid4(),
            subscriptionId: $subscriptionId1,
            amountPaid: 1000,
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $billing2 = new BillingHistory(
            id: Uuid::uuid4(),
            subscriptionId: $subscriptionId2,
            amountPaid: 2000,
            paidAt: $this->paidAt,
            createdAt: $this->createdAt,
        );

        $this->assertNotEquals($billing1->subscriptionId(), $billing2->subscriptionId());
    }

    public function test_handles_different_payment_dates(): void
    {
        $date1 = new DateTimeImmutable('2026-01-15 10:00:00');
        $date2 = new DateTimeImmutable('2026-02-15 10:00:00');

        $billing1 = $this->createBillingHistory(paidAt: $date1);
        $billing2 = $this->createBillingHistory(paidAt: $date2);

        $this->assertNotEquals($billing1->paidAt(), $billing2->paidAt());
    }

    private function createBillingHistory(
        ?int $amountPaid = 4990,
        ?DateTimeImmutable $paidAt = null,
    ): BillingHistory {
        return new BillingHistory(
            id: $this->id,
            subscriptionId: $this->subscriptionId,
            amountPaid: $amountPaid,
            paidAt: $paidAt ?? $this->paidAt,
            createdAt: $this->createdAt,
        );
    }
}
