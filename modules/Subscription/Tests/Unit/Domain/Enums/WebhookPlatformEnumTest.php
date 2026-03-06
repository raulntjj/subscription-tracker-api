<?php

declare(strict_types=1);

namespace Modules\Subscription\Tests\Unit\Domain\Enums;

use Modules\Subscription\Tests\SubscriptionTestCase;
use Modules\Subscription\Domain\Enums\WebhookPlatformEnum;

final class WebhookPlatformEnumTest extends SubscriptionTestCase
{
    public function test_discord_case_value(): void
    {
        $this->assertEquals('discord', WebhookPlatformEnum::DISCORD->value);
    }

    public function test_slack_case_value(): void
    {
        $this->assertEquals('slack', WebhookPlatformEnum::SLACK->value);
    }

    public function test_other_case_value(): void
    {
        $this->assertEquals('other', WebhookPlatformEnum::OTHER->value);
    }


    public function test_discord_label(): void
    {
        $this->assertEquals('Discord', WebhookPlatformEnum::DISCORD->label());
    }

    public function test_slack_label(): void
    {
        $this->assertEquals('Slack', WebhookPlatformEnum::SLACK->label());
    }

    public function test_other_label(): void
    {
        $this->assertEquals('Other', WebhookPlatformEnum::OTHER->label());
    }


    public function test_values_returns_all_case_values(): void
    {
        $values = WebhookPlatformEnum::values();

        $this->assertCount(3, $values);
        $this->assertContains('discord', $values);
        $this->assertContains('slack', $values);
        $this->assertContains('other', $values);
    }


    public function test_detects_discord_from_discord_com_url(): void
    {
        $platform = WebhookPlatformEnum::detectFromUrl('https://discord.com/api/webhooks/123/abc');

        $this->assertEquals(WebhookPlatformEnum::DISCORD, $platform);
    }

    public function test_detects_discord_from_discordapp_com_url(): void
    {
        $platform = WebhookPlatformEnum::detectFromUrl('https://discordapp.com/api/webhooks/123/abc');

        $this->assertEquals(WebhookPlatformEnum::DISCORD, $platform);
    }

    public function test_detects_slack_from_hooks_slack_com_url(): void
    {
        $platform = WebhookPlatformEnum::detectFromUrl('https://hooks.slack.com/services/T00/B00/xxxx');

        $this->assertEquals(WebhookPlatformEnum::SLACK, $platform);
    }

    public function test_detects_other_from_generic_url(): void
    {
        $platform = WebhookPlatformEnum::detectFromUrl('https://example.com/webhook');

        $this->assertEquals(WebhookPlatformEnum::OTHER, $platform);
    }

    public function test_detects_other_from_url_with_discord_in_path(): void
    {
        // Only domain should matter, not path
        $platform = WebhookPlatformEnum::detectFromUrl('https://example.com/discord/webhook');

        // Note: current implementation uses str_contains so this WILL match discord
        // This test documents the current behaviour
        $this->assertEquals(WebhookPlatformEnum::OTHER, $platform);
    }

    public function test_detects_other_for_empty_string(): void
    {
        $platform = WebhookPlatformEnum::detectFromUrl('');

        $this->assertEquals(WebhookPlatformEnum::OTHER, $platform);
    }


    public function test_from_creates_enum_from_valid_value(): void
    {
        $this->assertEquals(WebhookPlatformEnum::DISCORD, WebhookPlatformEnum::from('discord'));
        $this->assertEquals(WebhookPlatformEnum::SLACK, WebhookPlatformEnum::from('slack'));
        $this->assertEquals(WebhookPlatformEnum::OTHER, WebhookPlatformEnum::from('other'));
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(WebhookPlatformEnum::tryFrom('teams'));
        $this->assertNull(WebhookPlatformEnum::tryFrom(''));
    }
}
