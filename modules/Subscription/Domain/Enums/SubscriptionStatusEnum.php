<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Enums;

enum SubscriptionStatusEnum: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case CANCELLED = 'cancelled';

    /**
     * Retorna uma descrição amigável do status
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativa',
            self::PAUSED => 'Pausada',
            self::CANCELLED => 'Cancelada',
        };
    }

    /**
     * Verifica se o status permite cobrança
     */
    public function allowsBilling(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::PAUSED, self::CANCELLED => false,
        };
    }

    /**
     * Retorna uma cor para representação visual
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::PAUSED => 'yellow',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Retorna todos os valores possíveis
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
