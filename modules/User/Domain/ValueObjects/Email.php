<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $normalized = strtolower(trim($email));
        $this->validate($normalized);
        $this->value = $normalized;
    }

    private function validate(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(__('User::exception.invalid_email_format', ['email' => $email]));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
