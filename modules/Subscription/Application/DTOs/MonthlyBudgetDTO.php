<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\DTOs;

/**
 * DTO para representar o orçamento mensal de assinaturas
 */
final readonly class MonthlyBudgetDTO
{
    /**
     * @param int $totalCommitted Total já comprometido no mês (em centavos)
     * @param int $upcomingBills Total de contas a vencer no mês (em centavos)
     * @param array $breakdown Breakdown por categoria ['Streaming' => 5000, 'DevTools' => 3000]
     * @param string $currency Moeda dos valores
     * @param int $totalMonthly Total mensal (totalCommitted + upcomingBills)
     */
    public function __construct(
        public int $totalCommitted,
        public int $upcomingBills,
        public array $breakdown,
        public string $currency,
        public int $totalMonthly,
    ) {}

    /**
     * Converte DTO para array com valores formatados
     */
    public function toArray(): array
    {
        return [
            'total_committed' => $this->totalCommitted,
            'total_committed_formatted' => $this->formatPrice($this->totalCommitted),
            'upcoming_bills' => $this->upcomingBills,
            'upcoming_bills_formatted' => $this->formatPrice($this->upcomingBills),
            'total_monthly' => $this->totalMonthly,
            'total_monthly_formatted' => $this->formatPrice($this->totalMonthly),
            'currency' => $this->currency,
            'breakdown' => $this->formatBreakdown(),
        ];
    }

    /**
     * Formata o breakdown com valores formatados
     */
    private function formatBreakdown(): array
    {
        $formatted = [];
        
        foreach ($this->breakdown as $category => $amount) {
            $formatted[] = [
                'category' => $category,
                'amount' => $amount,
                'amount_formatted' => $this->formatPrice($amount),
                'percentage' => $this->totalMonthly > 0 
                    ? round(($amount / $this->totalMonthly) * 100, 2) 
                    : 0,
            ];
        }
        
        // Ordena por valor decrescente
        usort($formatted, fn($a, $b) => $b['amount'] <=> $a['amount']);
        
        return $formatted;
    }

    /**
     * Formata preço baseado na moeda
     */
    private function formatPrice(int $priceInCents): string
    {
        $amount = $priceInCents / 100;
        
        return match ($this->currency) {
            'BRL' => sprintf('R$ %.2f', $amount),
            'USD' => sprintf('$ %.2f', $amount),
            'EUR' => sprintf('€ %.2f', $amount),
            default => sprintf('%.2f %s', $amount, $this->currency),
        };
    }
}
