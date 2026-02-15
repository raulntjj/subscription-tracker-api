<?php

declare(strict_types=1);

namespace Modules\Subscription\Domain\Enums;

enum CurrencyEnum: string
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';

    /**
     * Retorna o símbolo da moeda
     */
    public function symbol(): string
    {
        return match ($this) {
            self::BRL => 'R$',
            self::USD => '$',
            self::EUR => '€',
        };
    }

    /**
     * Retorna o nome completo da moeda
     */
    public function label(): string
    {
        return match ($this) {
            self::BRL => 'Real Brasileiro',
            self::USD => 'Dólar Americano',
            self::EUR => 'Euro',
        };
    }

    /**
     * Retorna o código ISO 4217
     */
    public function code(): string
    {
        return $this->value;
    }

    /**
     * Retorna todos os valores possíveis
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Formata um valor (em centavos) para a moeda
     */
    public function format(int $amountInCents): string
    {
        $amount = $amountInCents / 100;

        return match ($this) {
            self::BRL => sprintf('R$ %.2f', $amount),
            self::USD => sprintf('$ %.2f', $amount),
            self::EUR => sprintf('€ %.2f', $amount),
        };
    }
}
