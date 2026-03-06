<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Enums;

enum WebhookPlatformEnum: string
{
    case DISCORD = 'discord';
    case SLACK = 'slack';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DISCORD => 'Discord',
            self::SLACK => 'Slack',
            self::OTHER => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Detecta a plataforma com base na URL do webhook
     */
    public static function detectFromUrl(string $url): self
    {
        if (str_contains($url, 'discord.com') || str_contains($url, 'discordapp.com')) {
            return self::DISCORD;
        }

        if (str_contains($url, 'hooks.slack.com')) {
            return self::SLACK;
        }

        return self::OTHER;
    }
}
