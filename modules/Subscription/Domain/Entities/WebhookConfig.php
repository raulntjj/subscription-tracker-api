<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

final class WebhookConfig
{
    private UuidInterface $id;
    private UuidInterface $userId;
    private string $url;
    private ?string $secret;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        UuidInterface $id,
        UuidInterface $userId,
        string $url,
        ?string $secret,
        bool $isActive,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->validateUrl($url);
        
        $this->id = $id;
        $this->userId = $userId;
        $this->url = $url;
        $this->secret = $secret;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    private function validateUrl(string $url): void
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Webhook URL cannot be empty');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid webhook URL format');
        }

        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            throw new \InvalidArgumentException('Webhook URL must use HTTP or HTTPS protocol');
        }
    }

    // Getters
    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function userId(): UuidInterface
    {
        return $this->userId;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function secret(): ?string
    {
        return $this->secret;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Business methods
    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function changeUrl(string $url): void
    {
        $this->validateUrl($url);
        $this->url = $url;
    }

    public function changeSecret(?string $secret): void
    {
        $this->secret = $secret;
    }

    public function updateTimestamps(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
