<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Password
{
    private string $hashedValue;

    private function __construct(string $hashedValue)
    {
        $this->hashedValue = $hashedValue;
    }

    public static function fromPlainText(string $plainPassword): self
    {
        if (strlen($plainPassword) < 8) {
            throw new InvalidArgumentException(__('User::message.password_min_length'));
        }

        return new self(bcrypt($plainPassword));
    }

    public static function fromHash(string $hashedValue): self
    {
        return new self($hashedValue);
    }

    public function value(): string
    {
        return $this->hashedValue;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }

    public function __toString(): string
    {
        return $this->hashedValue;
    }
}
