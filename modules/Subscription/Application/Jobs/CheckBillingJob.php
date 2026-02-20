<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Jobs;

use Throwable;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Subscription\Domain\Entities\Subscription;
use Modules\Subscription\Domain\Entities\BillingHistory;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Domain\Contracts\BillingHistoryRepositoryInterface;

/**
 * Job para verificar e processar assinaturas que vencem hoje
 *
 * Este job identifica faturamentos do dia, registra no BillingHistory,
 * atualiza a next_billing_date da assinatura e loga todas as ações.
 */
final class CheckBillingJob implements ShouldQueue
{
    use Loggable;
    use Queueable;
    use Dispatchable;
    use SerializesModels;
    use InteractsWithQueue;

    /**
     * Número de tentativas em caso de falha
     */
    public int $tries = 3;

    /**
     * Timeout em segundos
     */
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('billing');
    }

    /**
     * Executa o job
     */
    public function handle(
        SubscriptionRepositoryInterface $subscriptionRepository,
        BillingHistoryRepositoryInterface $billingHistoryRepository,
    ): void {
        $this->logger()->info('Starting billing check job');

        try {
            // Busca todas as assinaturas que devem ser faturadas hoje
            $subscriptions = $subscriptionRepository->findDueForBillingToday();

            $processedCount = 0;
            $failedCount = 0;

            foreach ($subscriptions as $subscription) {
                try {
                    $this->processSubscriptionBilling(
                        $subscription,
                        $subscriptionRepository,
                        $billingHistoryRepository,
                    );

                    $processedCount++;
                } catch (Throwable $e) {
                    $failedCount++;

                    $this->logger()->error('Failed to process subscription billing', [
                        'subscription_id' => $subscription->id()->toString(),
                        'subscription_name' => $subscription->name(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $this->logger()->info('Billing check job completed', [
                'total_subscriptions' => count($subscriptions),
                'processed' => $processedCount,
                'failed' => $failedCount,
            ]);
        } catch (Throwable $e) {
            $this->logger()->error('Billing check job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Processa o faturamento de uma assinatura
     */
    private function processSubscriptionBilling(
        Subscription $subscription,
        SubscriptionRepositoryInterface $subscriptionRepository,
        BillingHistoryRepositoryInterface $billingHistoryRepository,
    ): void {
        $this->logger()->info(message: 'Processing subscription billing', context: [
            'subscription_id' => $subscription->id()->toString(),
            'subscription_name' => $subscription->name(),
            'amount' => $subscription->price(),
            'billing_cycle' => $subscription->billingCycle()->value,
        ]);

        // 1. Cria o registro no BillingHistory
        $billingHistory = new BillingHistory(
            id: Uuid::uuid4(),
            subscriptionId: $subscription->id(),
            amountPaid: $subscription->price(),
            paidAt: new DateTimeImmutable('now'),
            createdAt: new DateTimeImmutable('now'),
        );

        $billingHistoryRepository->save($billingHistory);

        $this->logger()->info('Billing history created', [
            'billing_history_id' => $billingHistory->id()->toString(),
            'subscription_id' => $subscription->id()->toString(),
            'amount_paid' => $subscription->price(),
        ]);

        // 2. Atualiza a next_billing_date da assinatura
        $oldBillingDate = $subscription->nextBillingDate()->format('Y-m-d');
        $nextBillingDate = $subscription->calculateNextBillingDate();
        $subscription->updateNextBillingDate(newDate: $nextBillingDate);

        $subscriptionRepository->update(entity: $subscription);

        $this->logger()->info(message: 'Subscription next billing date updated', context: [
            'subscription_id' => $subscription->id()->toString(),
            'old_billing_date' => $oldBillingDate,
            'new_billing_date' => $nextBillingDate->format('Y-m-d'),
        ]);

        $this->dispatchWebhook(
            subscription: $subscription,
            billingHistory: $billingHistory,
            nextBillingDate: $nextBillingDate,
        );
    }

    /**
     * Despacha webhook de renovação de assinatura
     */
    private function dispatchWebhook(
        Subscription $subscription,
        BillingHistory $billingHistory,
        DateTimeImmutable $nextBillingDate,
    ): void {
        try {
            $subscriptionId = $subscription->id()->toString();
            $userId = $subscription->userId()->toString();

            $eventData = [
                'subscription_id' => $subscriptionId,
                'subscription_name' => $subscription->name(),
                'amount' => $subscription->price(),
                'currency' => $subscription->currency()->value,
                'billing_cycle' => $subscription->billingCycle()->value,
                'billing_history_id' => $billingHistory->id()->toString(),
                'billing_date' => $billingHistory->paidAt()->format('Y-m-d H:i:s'),
                'next_billing_date' => $nextBillingDate->format('Y-m-d'),
                'user_id' => $userId,
                'occurred_at' => now()->toIso8601String(),
                'status' => $subscription->status()->value,
            ];

            DispatchWebhookJob::dispatch(
                $subscriptionId,
                $userId,
                $billingHistory->id()->toString(),
                $eventData,
            );

            $this->logger()->info(message: 'Webhook dispatched for subscription renewal', context: [
                'subscription_id' => $subscriptionId,
                'user_id' => $userId,
                'billing_history_id' => $billingHistory->id()->toString(),
            ]);
        } catch (Throwable $e) {
            $this->logger()->warning('Failed to dispatch webhook for subscription renewal', context: [
                'subscription_id' => $subscription->id()->toString(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lida com falha do job
     */
    public function failed(Throwable $exception): void
    {
        $this->logger()->error('Billing check job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);
    }
}
