<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Enums;

enum BillingCycleEnum: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * Retorna o número de meses do ciclo de cobrança
     */
    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::YEARLY => 12,
        };
    }

    /**
     * Retorna uma descrição amigável do ciclo
     */
    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Mensal',
            self::YEARLY => 'Anual',
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
