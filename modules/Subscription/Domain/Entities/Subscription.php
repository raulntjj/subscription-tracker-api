<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\Enums\CurrencyEnum;
use Modules\Subscription\Domain\Enums\BillingCycleEnum;
use Modules\Subscription\Domain\Enums\SubscriptionStatusEnum;

final class Subscription
{
    private UuidInterface $id;
    private string $name;
    private int $price; // Armazenado em centavos para evitar problemas de precisão
    private CurrencyEnum $currency;
    private BillingCycleEnum $billingCycle;
    private DateTimeImmutable $nextBillingDate;
    private string $category;
    private SubscriptionStatusEnum $status;
    private UuidInterface $userId;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        UuidInterface $id,
        string $name,
        int $price,
        CurrencyEnum $currency,
        BillingCycleEnum $billingCycle,
        DateTimeImmutable $nextBillingDate,
        string $category,
        SubscriptionStatusEnum $status,
        UuidInterface $userId,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->validatePrice($price);
        $this->validateNextBillingDate($nextBillingDate);

        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->currency = $currency;
        $this->billingCycle = $billingCycle;
        $this->nextBillingDate = $nextBillingDate;
        $this->category = $category;
        $this->status = $status;
        $this->userId = $userId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Getters
    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): int
    {
        return $this->price;
    }

    public function currency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function billingCycle(): BillingCycleEnum
    {
        return $this->billingCycle;
    }

    public function nextBillingDate(): DateTimeImmutable
    {
        return $this->nextBillingDate;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function status(): SubscriptionStatusEnum
    {
        return $this->status;
    }

    public function userId(): UuidInterface
    {
        return $this->userId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Business Methods
    public function changeName(string $newName): void
    {
        $this->name = $newName;
        $this->updateTimestamp();
    }

    public function changePrice(int $newPrice): void
    {
        $this->validatePrice($newPrice);
        $this->price = $newPrice;
        $this->updateTimestamp();
    }

    public function changeBillingCycle(BillingCycleEnum $newCycle): void
    {
        $this->billingCycle = $newCycle;
        $this->updateTimestamp();
    }

    public function changeCategory(string $newCategory): void
    {
        $this->category = $newCategory;
        $this->updateTimestamp();
    }

    public function activate(): void
    {
        $this->status = SubscriptionStatusEnum::ACTIVE;
        $this->updateTimestamp();
    }

    public function pause(): void
    {
        $this->status = SubscriptionStatusEnum::PAUSED;
        $this->updateTimestamp();
    }

    public function cancel(): void
    {
        $this->status = SubscriptionStatusEnum::CANCELLED;
        $this->updateTimestamp();
    }

    public function updateNextBillingDate(DateTimeImmutable $newDate): void
    {
        $this->validateNextBillingDate($newDate);
        $this->nextBillingDate = $newDate;
        $this->updateTimestamp();
    }

    public function calculateNextBillingDate(): DateTimeImmutable
    {
        $months = $this->billingCycle->months();
        $interval = new \DateInterval("P{$months}M");

        return $this->nextBillingDate->add($interval);
    }

    /**
     * Normaliza o preço para valor mensal (usado para cálculos de budget)
     */
    public function normalizedMonthlyPrice(): int
    {
        if ($this->billingCycle === BillingCycleEnum::MONTHLY) {
            return $this->price;
        }

        // Se for anual, divide por 12
        return (int) round($this->price / 12);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatusEnum::ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === SubscriptionStatusEnum::PAUSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatusEnum::CANCELLED;
    }

    public function isDueForBilling(): bool
    {
        $today = new DateTimeImmutable('today');
        return $this->isActive() && $this->nextBillingDate->format('Y-m-d') === $today->format('Y-m-d');
    }

    // Private Methods
    private function validatePrice(int $price): void
    {
        if ($price < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
    }

    private function validateNextBillingDate(DateTimeImmutable $date): void
    {
        $today = new DateTimeImmutable('today');
        if ($date < $today) {
            throw new \InvalidArgumentException('Next billing date must be in the future or today');
        }
    }

    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
