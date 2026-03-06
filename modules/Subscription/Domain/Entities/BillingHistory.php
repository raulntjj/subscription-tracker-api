<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\ValueObjects\Money;

final class BillingHistory
{
    private UuidInterface $id;
    private UuidInterface $subscriptionId;
    private Money $amountPaid;
    private DateTimeImmutable $paidAt;
    private DateTimeImmutable $createdAt;

    public function __construct(
        UuidInterface $id,
        UuidInterface $subscriptionId,
        Money $amountPaid,
        DateTimeImmutable $paidAt,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->subscriptionId = $subscriptionId;
        $this->amountPaid = $amountPaid;
        $this->paidAt = $paidAt;
        $this->createdAt = $createdAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function subscriptionId(): UuidInterface
    {
        return $this->subscriptionId;
    }

    public function amountPaid(): Money
    {
        return $this->amountPaid;
    }

    public function paidAt(): DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
