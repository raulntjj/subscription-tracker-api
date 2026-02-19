<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Listeners;

use Modules\Shared\Infrastructure\Logging\StructuredLogger;
use Modules\Subscription\Domain\Events\SubscriptionRenewed;
use Modules\Subscription\Application\Jobs\DispatchWebhookJob;

/**
 * Listener que escuta o evento SubscriptionRenewed e despacha o job de webhook
 */
final class DispatchWebhookOnSubscriptionRenewed
{
    private StructuredLogger $logger;

    public function __construct(StructuredLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle the event
     */
    public function handle(SubscriptionRenewed $event): void
    {
        $this->logger->info('Dispatching webhook job for subscription renewal', [
            'subscription_id' => $event->getSubscriptionId()->toString(),
            'user_id' => $event->getUserId()->toString(),
            'billing_history_id' => $event->getBillingHistoryId()->toString(),
        ]);

        // Despachar job para fila webhook do RabbitMQ
        DispatchWebhookJob::dispatch(
            $event->getSubscriptionId()->toString(),
            $event->getUserId()->toString(),
            $event->getBillingHistoryId()->toString(),
            $event->toArray()
        )->onQueue('webhook');
    }
}
