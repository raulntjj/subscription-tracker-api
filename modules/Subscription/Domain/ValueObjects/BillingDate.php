<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\ValueObjects;

use JsonSerializable;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class BillingDate implements JsonSerializable
{
    private DateTimeImmutable $date;

    private function __construct(DateTimeImmutable $date)
    {
        $this->validate($date);
        $this->date = $date;
    }

    public static function fromDateTime(DateTimeImmutable $date): self
    {
        return new self($date);
    }

    public static function fromString(string $date): self
    {
        $dateTime = new DateTimeImmutable($date);
        return new self($dateTime);
    }

    public static function today(): self
    {
        return new self(new DateTimeImmutable('today'));
    }

    private function validate(DateTimeImmutable $date): void
    {
        $today = new DateTimeImmutable('today');
        if ($date < $today) {
            throw new InvalidArgumentException(__('Subscription::exception.next_billing_date_future'));
        }
    }

    public function value(): DateTimeImmutable
    {
        return $this->date;
    }

    public function format(string $format = 'Y-m-d'): string
    {
        return $this->date->format($format);
    }

    public function addMonths(int $months): self
    {
        $newDate = $this->date->add(new \DateInterval("P{$months}M"));
        return new self($newDate);
    }

    public function isToday(): bool
    {
        $today = new DateTimeImmutable('today');
        return $this->date->format('Y-m-d') === $today->format('Y-m-d');
    }

    public function isFuture(): bool
    {
        $today = new DateTimeImmutable('today');
        return $this->date > $today;
    }

    public function isPast(): bool
    {
        $today = new DateTimeImmutable('today');
        return $this->date < $today;
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->date->format('Y-m-d') === $other->date->format('Y-m-d');
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function jsonSerialize(): string
    {
        return $this->format();
    }
}
