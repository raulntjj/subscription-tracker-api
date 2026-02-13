<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Events;

use Ramsey\Uuid\UuidInterface;
use DateTimeImmutable;

/**
 * Evento de domínio disparado quando uma subscrição é renovada
 */
final class SubscriptionRenewed
{
    private UuidInterface $subscriptionId;
    private UuidInterface $userId;
    private UuidInterface $billingHistoryId;
    private string $subscriptionName;
    private int $amount;
    private string $currency;
    private DateTimeImmutable $billingDate;
    private DateTimeImmutable $nextBillingDate;
    private DateTimeImmutable $occurredAt;

    public function __construct(
        UuidInterface $subscriptionId,
        UuidInterface $userId,
        UuidInterface $billingHistoryId,
        string $subscriptionName,
        int $amount,
        string $currency,
        DateTimeImmutable $billingDate,
        DateTimeImmutable $nextBillingDate,
        ?DateTimeImmutable $occurredAt = null
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->userId = $userId;
        $this->billingHistoryId = $billingHistoryId;
        $this->subscriptionName = $subscriptionName;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->billingDate = $billingDate;
        $this->nextBillingDate = $nextBillingDate;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }

    public function getSubscriptionId(): UuidInterface
    {
        return $this->subscriptionId;
    }

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    public function getBillingHistoryId(): UuidInterface
    {
        return $this->billingHistoryId;
    }

    public function getSubscriptionName(): string
    {
        return $this->subscriptionName;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBillingDate(): DateTimeImmutable
    {
        return $this->billingDate;
    }

    public function getNextBillingDate(): DateTimeImmutable
    {
        return $this->nextBillingDate;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'subscription_id' => $this->subscriptionId->toString(),
            'user_id' => $this->userId->toString(),
            'billing_history_id' => $this->billingHistoryId->toString(),
            'subscription_name' => $this->subscriptionName,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'billing_date' => $this->billingDate->format('Y-m-d'),
            'next_billing_date' => $this->nextBillingDate->format('Y-m-d'),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
