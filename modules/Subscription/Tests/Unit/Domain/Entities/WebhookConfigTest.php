<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Domain\Entities;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Entities\WebhookConfig;

final class WebhookConfigTest extends SubscriptionTestCase
{
    private UuidInterface $id;
    private UuidInterface $userId;
    private DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = Uuid::uuid4();
        $this->userId = Uuid::uuid4();
        $this->createdAt = new DateTimeImmutable('2026-02-20 12:00:00');
    }

    public function test_creates_webhook_config_with_required_fields(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'https://example.com/webhook',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals($this->id, $webhookConfig->id());
        $this->assertEquals($this->userId, $webhookConfig->userId());
        $this->assertEquals('https://example.com/webhook', $webhookConfig->url());
        $this->assertEquals('secret123', $webhookConfig->secret());
        $this->assertTrue($webhookConfig->isActive());
        $this->assertEquals($this->createdAt, $webhookConfig->createdAt());
        $this->assertNull($webhookConfig->updatedAt());
    }

    public function test_creates_webhook_config_without_secret(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'https://example.com/webhook',
            secret: null,
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertNull($webhookConfig->secret());
    }

    public function test_creates_webhook_config_with_updated_at(): void
    {
        $updatedAt = new DateTimeImmutable('2026-02-21 10:00:00');

        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'https://example.com/webhook',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertEquals($updatedAt, $webhookConfig->updatedAt());
    }

    public function test_id_returns_uuid_interface(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $this->assertInstanceOf(UuidInterface::class, $webhookConfig->id());
    }

    public function test_user_id_returns_uuid_interface(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $this->assertInstanceOf(UuidInterface::class, $webhookConfig->userId());
    }

    public function test_throws_exception_for_empty_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: '',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );
    }

    public function test_throws_exception_for_invalid_url_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'not-a-valid-url',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );
    }

    public function test_throws_exception_for_url_without_http_protocol(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'ftp://example.com/webhook',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );
    }

    public function test_accepts_http_url(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'http://example.com/webhook',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('http://example.com/webhook', $webhookConfig->url());
    }

    public function test_accepts_https_url(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'https://example.com/webhook',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com/webhook', $webhookConfig->url());
    }

    public function test_activate_sets_is_active_to_true(): void
    {
        $webhookConfig = $this->createWebhookConfig(isActive: false);

        $webhookConfig->activate();

        $this->assertTrue($webhookConfig->isActive());
    }

    public function test_deactivate_sets_is_active_to_false(): void
    {
        $webhookConfig = $this->createWebhookConfig(isActive: true);

        $webhookConfig->deactivate();

        $this->assertFalse($webhookConfig->isActive());
    }

    public function test_change_url_updates_url(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $webhookConfig->changeUrl('https://newdomain.com/webhook');

        $this->assertEquals('https://newdomain.com/webhook', $webhookConfig->url());
    }

    public function test_change_url_throws_exception_for_invalid_url(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $this->expectException(InvalidArgumentException::class);

        $webhookConfig->changeUrl('invalid-url');
    }

    public function test_change_secret_updates_secret(): void
    {
        $webhookConfig = $this->createWebhookConfig(secret: 'old-secret');

        $webhookConfig->changeSecret('new-secret');

        $this->assertEquals('new-secret', $webhookConfig->secret());
    }

    public function test_change_secret_accepts_null(): void
    {
        $webhookConfig = $this->createWebhookConfig(secret: 'old-secret');

        $webhookConfig->changeSecret(null);

        $this->assertNull($webhookConfig->secret());
    }

    public function test_update_timestamps_sets_updated_at(): void
    {
        $webhookConfig = $this->createWebhookConfig();
        $originalUpdatedAt = $webhookConfig->updatedAt();

        $webhookConfig->updateTimestamps();

        $this->assertNotEquals($originalUpdatedAt, $webhookConfig->updatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $webhookConfig->updatedAt());
    }

    public function test_accepts_url_with_port(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'https://example.com:8080/webhook',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com:8080/webhook', $webhookConfig->url());
    }

    public function test_accepts_url_with_query_parameters(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'https://example.com/webhook?key=value',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com/webhook?key=value', $webhookConfig->url());
    }

    public function test_accepts_url_with_path(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: 'https://example.com/api/v1/webhooks/subscription',
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com/api/v1/webhooks/subscription', $webhookConfig->url());
    }

    public function test_created_at_returns_datetime_immutable(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $this->assertInstanceOf(DateTimeImmutable::class, $webhookConfig->createdAt());
    }

    public function test_creates_inactive_webhook(): void
    {
        $webhookConfig = $this->createWebhookConfig(isActive: false);

        $this->assertFalse($webhookConfig->isActive());
    }

    private function createWebhookConfig(
        ?string $url = 'https://example.com/webhook',
        ?string $secret = 'secret123',
        ?bool $isActive = true,
        ?DateTimeImmutable $updatedAt = null,
    ): WebhookConfig {
        return new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: $url,
            secret: $secret,
            isActive: $isActive,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
        );
    }
}
