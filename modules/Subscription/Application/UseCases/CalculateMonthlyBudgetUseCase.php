<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use DateTimeImmutable;
use Modules\Shared\Infrastructure\Logging\StructuredLogger;
use Modules\Subscription\Application\DTOs\MonthlyBudgetDTO;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

/**
 * Caso de uso para calcular o orçamento mensal de assinaturas
 * 
 * Este é o diferencial técnico que calcula:
 * - total_committed: O que já venceu/foi pago no mês
 * - upcoming_bills: O que ainda vai vencer no mês
 * - breakdown: Valores por categoria
 */
final readonly class CalculateMonthlyBudgetUseCase
{
    private StructuredLogger $logger;

    public function __construct(
        private SubscriptionRepositoryInterface $repository,
    ) {
        $this->logger = new StructuredLogger('Subscription');
    }

    /**
     * Executa o cálculo do orçamento mensal para um usuário
     *
     * @param string $userId UUID do usuário
     * @param string $currency Moeda para o cálculo (padrão: BRL)
     * @return MonthlyBudgetDTO
     */
    public function execute(string $userId, string $currency = 'BRL'): MonthlyBudgetDTO
    {
        $this->logger->info('Calculating monthly budget', [
            'user_id' => $userId,
            'currency' => $currency,
        ]);

        try {
            // Busca todas as assinaturas ativas do usuário
            $subscriptions = $this->repository->findActiveByUserId($userId);

            $totalCommitted = 0;
            $upcomingBills = 0;
            $breakdown = [];

            $today = new DateTimeImmutable('today');
            $startOfMonth = new DateTimeImmutable('first day of this month');
            $endOfMonth = new DateTimeImmutable('last day of this month');

            foreach ($subscriptions as $subscription) {
                // Normaliza o preço para valor mensal
                $monthlyPrice = $this->normalizeToMonthlyPrice(
                    $subscription->price,
                    $subscription->billing_cycle
                );

                // Filtra apenas assinaturas na moeda solicitada
                if ($subscription->currency !== $currency) {
                    continue;
                }

                // Adiciona ao breakdown por categoria
                $category = $subscription->category;
                if (!isset($breakdown[$category])) {
                    $breakdown[$category] = 0;
                }
                $breakdown[$category] += $monthlyPrice;

                // Determina se é committed ou upcoming
                $nextBillingDate = new DateTimeImmutable($subscription->next_billing_date);

                if ($nextBillingDate >= $startOfMonth && $nextBillingDate <= $today) {
                    // Já venceu neste mês
                    $totalCommitted += $monthlyPrice;
                } elseif ($nextBillingDate > $today && $nextBillingDate <= $endOfMonth) {
                    // Ainda vai vencer neste mês
                    $upcomingBills += $monthlyPrice;
                }
            }

            $totalMonthly = $totalCommitted + $upcomingBills;

            $this->logger->info('Monthly budget calculated successfully', [
                'user_id' => $userId,
                'total_committed' => $totalCommitted,
                'upcoming_bills' => $upcomingBills,
                'total_monthly' => $totalMonthly,
                'categories_count' => count($breakdown),
            ]);

            return new MonthlyBudgetDTO(
                totalCommitted: $totalCommitted,
                upcomingBills: $upcomingBills,
                breakdown: $breakdown,
                currency: $currency,
                totalMonthly: $totalMonthly,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to calculate monthly budget', [
                'user_id' => $userId,
                'currency' => $currency,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Normaliza o preço para valor mensal
     * Se for anual, divide por 12
     *
     * @param int $price Preço em centavos
     * @param string $billingCycle 'monthly' ou 'yearly'
     * @return int Preço mensal em centavos
     */
    private function normalizeToMonthlyPrice(int $price, string $billingCycle): int
    {
        if ($billingCycle === 'yearly') {
            return (int) round($price / 12);
        }

        return $price;
    }
}
