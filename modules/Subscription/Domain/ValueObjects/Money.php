<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\ValueObjects;

use JsonSerializable;
use InvalidArgumentException;

final readonly class Money implements JsonSerializable
{
    private int $amount; // Armazenado em centavos

    private function __construct(int $amount)
    {
        $this->validate($amount);
        $this->amount = $amount;
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromUnits(float $units): self
    {
        $cents = (int) round($units * 100);
        return new self($cents);
    }

    private function validate(int $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException(__('Subscription::exception.amount_cannot_negative'));
        }
    }

    public function toCents(): int
    {
        return $this->amount;
    }

    public function toUnits(): float
    {
        return $this->amount / 100;
    }

    public function add(self $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(self $other): self
    {
        return new self($this->amount - $other->amount);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor);
    }

    public function divide(int $divisor): self
    {
        if ($divisor === 0) {
            throw new InvalidArgumentException(__('Subscription::exception.division_by_zero'));
        }

        return new self((int) round($this->amount / $divisor));
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function isLessThan(self $other): bool
    {
        return $this->amount < $other->amount;
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->amount === $other->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function __toString(): string
    {
        return (string) $this->toUnits();
    }

    public function jsonSerialize(): int
    {
        return $this->amount;
    }
}
