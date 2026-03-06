<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Domain\ValueObjects\WebhookUrl;
use Modules\Subscription\Domain\Enums\WebhookPlatformEnum;

final class WebhookConfig
{
    private UuidInterface $id;
    private UuidInterface $userId;
    private WebhookUrl $url;
    private ?string $secret;
    private bool $isActive;
    private WebhookPlatformEnum $platform;
    private ?string $botName;
    private ?string $serverName;
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
        WebhookPlatformEnum $platform = WebhookPlatformEnum::OTHER,
        ?string $botName = null,
        ?string $serverName = null,
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->url = $url;
        $this->secret = $secret;
        $this->isActive = $isActive;
        $this->platform = $platform;
        $this->botName = $botName;
        $this->serverName = $serverName;
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

    public function platform(): WebhookPlatformEnum
    {
        return $this->platform;
    }

    public function botName(): ?string
    {
        return $this->botName;
    }

    public function serverName(): ?string
    {
        return $this->serverName;
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

    public function changePlatform(WebhookPlatformEnum $platform): void
    {
        $this->platform = $platform;
    }

    public function changeBotName(?string $botName): void
    {
        $this->botName = $botName;
    }

    public function changeServerName(?string $serverName): void
    {
        $this->serverName = $serverName;
    }

    public function updateTimestamps(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
