<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\ValueObjects;

use JsonSerializable;
use InvalidArgumentException;

final readonly class WebhookUrl implements JsonSerializable
{
    private string $value;

    private function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function fromString(string $url): self
    {
        return new self($url);
    }

    private function validate(string $url): void
    {
        if (empty($url)) {
            throw new InvalidArgumentException(__('Subscription::exception.webhook_url_empty'));
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(__('Subscription::exception.webhook_url_invalid'));
        }

        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            throw new InvalidArgumentException(__('Subscription::exception.webhook_url_protocol'));
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(?self $other): bool
    {
        if ($other === null) {
            return false;
        }

        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
