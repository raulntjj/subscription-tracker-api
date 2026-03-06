<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\ValueObjects\WebhookUrl;

final class WebhookConfig
{
    private UuidInterface $id;
    private UuidInterface $userId;
    private WebhookUrl $url;
    private ?string $secret;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        UuidInterface $id,
        UuidInterface $userId,
        WebhookUrl $url,
        ?string $secret,
        bool $isActive,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->url = $url;
        $this->secret = $secret;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
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

    public function url(): WebhookUrl
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

    public function changeUrl(WebhookUrl $url): void
    {
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
