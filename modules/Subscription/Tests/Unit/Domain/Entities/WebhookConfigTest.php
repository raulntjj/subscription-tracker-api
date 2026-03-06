<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Domain\Entities;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Entities\WebhookConfig;
use Modules\Subscription\Domain\ValueObjects\WebhookUrl;
use Modules\Subscription\Domain\Enums\WebhookPlatformEnum;

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
            url: WebhookUrl::fromString('https://example.com/webhook'),
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals($this->id, $webhookConfig->id());
        $this->assertEquals($this->userId, $webhookConfig->userId());
        $this->assertEquals('https://example.com/webhook', $webhookConfig->url()->value());
        $this->assertEquals('secret123', $webhookConfig->secret());
        $this->assertTrue($webhookConfig->isActive());
        $this->assertEquals($this->createdAt, $webhookConfig->createdAt());
        $this->assertNull($webhookConfig->updatedAt());
        $this->assertEquals(WebhookPlatformEnum::OTHER, $webhookConfig->platform());
        $this->assertNull($webhookConfig->botName());
        $this->assertNull($webhookConfig->serverName());
    }

    public function test_creates_webhook_config_without_secret(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: WebhookUrl::fromString('https://example.com/webhook'),
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
            url: WebhookUrl::fromString('https://example.com/webhook'),
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

        WebhookUrl::fromString('');
    }

    public function test_throws_exception_for_invalid_url_format(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebhookUrl::fromString('not-a-valid-url');
    }

    public function test_throws_exception_for_url_without_http_protocol(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WebhookUrl::fromString('ftp://example.com/webhook');
    }

    public function test_accepts_http_url(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: WebhookUrl::fromString('http://example.com/webhook'),
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('http://example.com/webhook', $webhookConfig->url()->value());
    }

    public function test_accepts_https_url(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: WebhookUrl::fromString('https://example.com/webhook'),
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com/webhook', $webhookConfig->url()->value());
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

        $webhookConfig->changeUrl(WebhookUrl::fromString('https://newdomain.com/webhook'));

        $this->assertEquals('https://newdomain.com/webhook', $webhookConfig->url()->value());
    }

    public function test_change_url_throws_exception_for_invalid_url(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $this->expectException(InvalidArgumentException::class);

        $webhookConfig->changeUrl(WebhookUrl::fromString('invalid-url'));
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
            url: WebhookUrl::fromString('https://example.com:8080/webhook'),
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com:8080/webhook', $webhookConfig->url()->value());
    }

    public function test_accepts_url_with_query_parameters(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: WebhookUrl::fromString('https://example.com/webhook?key=value'),
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com/webhook?key=value', $webhookConfig->url()->value());
    }

    public function test_accepts_url_with_path(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: WebhookUrl::fromString('https://example.com/api/v1/webhooks/subscription'),
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
        );

        $this->assertEquals('https://example.com/api/v1/webhooks/subscription', $webhookConfig->url()->value());
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

    public function test_creates_webhook_config_with_platform(): void
    {
        $webhookConfig = $this->createWebhookConfig(platform: WebhookPlatformEnum::DISCORD);

        $this->assertEquals(WebhookPlatformEnum::DISCORD, $webhookConfig->platform());
    }

    public function test_creates_webhook_config_with_bot_name(): void
    {
        $webhookConfig = $this->createWebhookConfig(botName: 'MyBot');

        $this->assertEquals('MyBot', $webhookConfig->botName());
    }

    public function test_creates_webhook_config_with_server_name(): void
    {
        $webhookConfig = $this->createWebhookConfig(serverName: 'My Server');

        $this->assertEquals('My Server', $webhookConfig->serverName());
    }

    public function test_creates_webhook_config_with_all_platform_fields(): void
    {
        $webhookConfig = new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: WebhookUrl::fromString('https://discord.com/api/webhooks/123/abc'),
            secret: 'secret123',
            isActive: true,
            createdAt: $this->createdAt,
            platform: WebhookPlatformEnum::DISCORD,
            botName: 'Notify Bot',
            serverName: 'Dev Server',
        );

        $this->assertEquals(WebhookPlatformEnum::DISCORD, $webhookConfig->platform());
        $this->assertEquals('Notify Bot', $webhookConfig->botName());
        $this->assertEquals('Dev Server', $webhookConfig->serverName());
    }

    public function test_change_platform_updates_platform(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $webhookConfig->changePlatform(WebhookPlatformEnum::SLACK);

        $this->assertEquals(WebhookPlatformEnum::SLACK, $webhookConfig->platform());
    }

    public function test_change_bot_name_updates_bot_name(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $webhookConfig->changeBotName('New Bot');

        $this->assertEquals('New Bot', $webhookConfig->botName());
    }

    public function test_change_bot_name_accepts_null(): void
    {
        $webhookConfig = $this->createWebhookConfig(botName: 'Old Bot');

        $webhookConfig->changeBotName(null);

        $this->assertNull($webhookConfig->botName());
    }

    public function test_change_server_name_updates_server_name(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $webhookConfig->changeServerName('New Server');

        $this->assertEquals('New Server', $webhookConfig->serverName());
    }

    public function test_change_server_name_accepts_null(): void
    {
        $webhookConfig = $this->createWebhookConfig(serverName: 'Old Server');

        $webhookConfig->changeServerName(null);

        $this->assertNull($webhookConfig->serverName());
    }

    public function test_default_platform_is_other(): void
    {
        $webhookConfig = $this->createWebhookConfig();

        $this->assertEquals(WebhookPlatformEnum::OTHER, $webhookConfig->platform());
    }

    private function createWebhookConfig(
        ?string $url = 'https://example.com/webhook',
        ?string $secret = 'secret123',
        ?bool $isActive = true,
        ?DateTimeImmutable $updatedAt = null,
        WebhookPlatformEnum $platform = WebhookPlatformEnum::OTHER,
        ?string $botName = null,
        ?string $serverName = null,
    ): WebhookConfig {
        return new WebhookConfig(
            id: $this->id,
            userId: $this->userId,
            url: WebhookUrl::fromString($url),
            secret: $secret,
            isActive: $isActive,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            platform: $platform,
            botName: $botName,
            serverName: $serverName,
        );
    }
}
