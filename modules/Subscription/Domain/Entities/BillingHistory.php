<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

final class BillingHistory
{
    private UuidInterface $id;
    private UuidInterface $subscriptionId;
    private int $amountPaid; // Armazenado em centavos
    private DateTimeImmutable $paidAt;
    private DateTimeImmutable $createdAt;

    public function __construct(
        UuidInterface $id,
        UuidInterface $subscriptionId,
        int $amountPaid,
        DateTimeImmutable $paidAt,
        DateTimeImmutable $createdAt,
    ) {
        $this->validateAmount($amountPaid);

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

    public function amountPaid(): int
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

    /**
     * Retorna o valor pago formatado (em reais/dólares)
     */
    public function amountPaidFormatted(): float
    {
        return $this->amountPaid / 100;
    }

    private function validateAmount(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount paid cannot be negative');
        }
    }
}
