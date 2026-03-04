<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Throwable;
use DateTimeImmutable;
use Laravel\Octane\Facades\Octane;
use Modules\Subscription\Application\DTOs\MonthlyBudgetDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;

/**
 * Caso de uso para calcular o orçamento mensal de assinaturas
 *
 * Este é o diferencial técnico que calcula:
 * - total_committed: O que já venceu/foi pago no mês
 * - upcoming_bills: O que ainda vai vencer no mês
 * - breakdown: Valores por categoria
 *
 * Utiliza Octane::concurrently para paralelizar o cálculo
 * de committed vs upcoming quando há muitas assinaturas.
 */
final readonly class CalculateMonthlyBudgetUseCase
{
    use Loggable;

    public function __construct(
        private SubscriptionRepositoryInterface $repository,
    ) {
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
        $this->logger()->info('Calculating monthly budget', [
            'user_id' => $userId,
            'currency' => $currency,
        ]);

        try {
            // Busca todas as assinaturas ativas do usuário
            $subscriptions = $this->repository->findActiveByUserId($userId);

            // Filtra apenas assinaturas na moeda solicitada
            $filteredSubscriptions = array_filter(
                $subscriptions,
                fn ($sub) => $sub->currency()->value === $currency,
            );

            $today = new DateTimeImmutable('today');

            [$committedResult, $upcomingResult] = Octane::concurrently([
                fn () => $this->calculateCommitted($filteredSubscriptions, $today),
                fn () => $this->calculateUpcoming($filteredSubscriptions, $today),
            ], 5000);

            $totalCommitted = $committedResult['total'];
            $upcomingBills = $upcomingResult['total'];
            $breakdown = $this->mergeBreakdowns(
                $committedResult['breakdown'],
                $upcomingResult['breakdown'],
            );

            $totalMonthly = $totalCommitted + $upcomingBills;

            $this->logger()->info('Monthly budget calculated successfully', [
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
        } catch (Throwable $e) {
            $this->logger()->error('Failed to calculate monthly budget', [
                'user_id' => $userId,
                'currency' => $currency,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calcula o total comprometido (assinaturas já vencidas no mês)
     *
     * @param array $subscriptions
     * @param DateTimeImmutable $today
     * @return array{total: int, breakdown: array<string, int>}
     */
    private function calculateCommitted(array $subscriptions, DateTimeImmutable $today): array
    {
        $total = 0;
        $breakdown = [];

        foreach ($subscriptions as $subscription) {
            if ($subscription->nextBillingDate() <= $today) {
                $monthlyPrice = $this->normalizeToMonthlyPrice(
                    $subscription->price(),
                    $subscription->billingCycle()->value,
                );

                $total += $monthlyPrice;

                $category = $subscription->category();
                $breakdown[$category] = ($breakdown[$category] ?? 0) + $monthlyPrice;
            }
        }

        return ['total' => $total, 'breakdown' => $breakdown];
    }

    /**
     * Calcula o total a vencer (assinaturas futuras no mês)
     *
     * @param array $subscriptions
     * @param DateTimeImmutable $today
     * @return array{total: int, breakdown: array<string, int>}
     */
    private function calculateUpcoming(array $subscriptions, DateTimeImmutable $today): array
    {
        $total = 0;
        $breakdown = [];

        foreach ($subscriptions as $subscription) {
            if ($subscription->nextBillingDate() > $today) {
                $monthlyPrice = $this->normalizeToMonthlyPrice(
                    $subscription->price(),
                    $subscription->billingCycle()->value,
                );

                $total += $monthlyPrice;

                $category = $subscription->category();
                $breakdown[$category] = ($breakdown[$category] ?? 0) + $monthlyPrice;
            }
        }

        return ['total' => $total, 'breakdown' => $breakdown];
    }

    /**
     * Merge breakdowns de committed e upcoming
     *
     * @param array<string, int> $committed
     * @param array<string, int> $upcoming
     * @return array<string, int>
     */
    private function mergeBreakdowns(array $committed, array $upcoming): array
    {
        $merged = $committed;

        foreach ($upcoming as $category => $amount) {
            $merged[$category] = ($merged[$category] ?? 0) + $amount;
        }

        return $merged;
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
