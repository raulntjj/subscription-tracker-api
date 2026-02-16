<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Jobs;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Subscription\Domain\Entities\BillingHistory;
use Modules\Shared\Infrastructure\Logging\StructuredLogger;
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
        $logger = new StructuredLogger('Subscription');

        $logger->info('Starting billing check job');

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
                        $logger
                    );

                    $processedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;

                    $logger->error('Failed to process subscription billing', [
                        'subscription_id' => $subscription->id,
                        'subscription_name' => $subscription->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $logger->info('Billing check job completed', [
                'total_subscriptions' => count($subscriptions),
                'processed' => $processedCount,
                'failed' => $failedCount,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Billing check job failed', [
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
        object $subscription,
        SubscriptionRepositoryInterface $subscriptionRepository,
        BillingHistoryRepositoryInterface $billingHistoryRepository,
        StructuredLogger $logger
    ): void {
        $logger->info('Processing subscription billing', [
            'subscription_id' => $subscription->id,
            'subscription_name' => $subscription->name,
            'amount' => $subscription->price,
            'billing_cycle' => $subscription->billing_cycle,
        ]);

        // 1. Cria o registro no BillingHistory
        $billingHistory = new BillingHistory(
            id: Uuid::uuid4(),
            subscriptionId: Uuid::fromString($subscription->id),
            amountPaid: $subscription->price,
            paidAt: new DateTimeImmutable('now'),
            createdAt: new DateTimeImmutable('now')
        );

        $billingHistoryRepository->save($billingHistory);

        $logger->info('Billing history created', [
            'billing_history_id' => $billingHistory->id()->toString(),
            'subscription_id' => $subscription->id,
            'amount_paid' => $subscription->price,
        ]);

        // 2. Atualiza a next_billing_date da assinatura
        $subscriptionEntity = $subscriptionRepository->findById(
            Uuid::fromString($subscription->id)
        );

        if ($subscriptionEntity === null) {
            throw new \RuntimeException("Subscription not found: {$subscription->id}");
        }

        $nextBillingDate = $subscriptionEntity->calculateNextBillingDate();
        $subscriptionEntity->updateNextBillingDate($nextBillingDate);

        $subscriptionRepository->update($subscriptionEntity);

        $logger->info('Subscription next billing date updated', [
            'subscription_id' => $subscription->id,
            'old_billing_date' => $subscription->next_billing_date,
            'new_billing_date' => $nextBillingDate->format('Y-m-d'),
        ]);

        // 3. Despachar webhook de renovação
        $this->dispatchWebhook(
            $subscription,
            $subscriptionEntity,
            $billingHistory,
            $nextBillingDate,
            $logger
        );
    }

    /**
     * Despacha webhook de renovação de assinatura
     */
    private function dispatchWebhook(
        object $subscription,
        object $subscriptionEntity,
        BillingHistory $billingHistory,
        \DateTimeImmutable $nextBillingDate,
        StructuredLogger $logger
    ): void {
        try {
            $eventData = [
                'subscription_id' => $subscription->id,
                'subscription_name' => $subscription->name,
                'amount' => $subscription->price,
                'currency' => $subscription->currency,
                'billing_cycle' => $subscription->billing_cycle,
                'billing_history_id' => $billingHistory->id()->toString(),
                'billing_date' => $billingHistory->paidAt()->format('Y-m-d H:i:s'),
                'next_billing_date' => $nextBillingDate->format('Y-m-d'),
                'user_id' => $subscription->user_id,
                'occurred_at' => now()->toIso8601String(),
                'status' => $subscription->status,
            ];

            DispatchWebhookJob::dispatch(
                $subscription->id,
                $subscription->user_id,
                $billingHistory->id()->toString(),
                $eventData
            );

            $logger->info('Webhook dispatched for subscription renewal', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'billing_history_id' => $billingHistory->id()->toString(),
            ]);
        } catch (\Throwable $e) {
            // Não falhar o billing por causa do webhook
            $logger->warning('Failed to dispatch webhook for subscription renewal', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lida com falha do job
     */
    public function failed(\Throwable $exception): void
    {
        $logger = app(StructuredLogger::class);

        $logger->error('Billing check job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);
    }
}
