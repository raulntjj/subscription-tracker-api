<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Jobs;

use Exception;
use Throwable;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Illuminate\Bus\Queueable;
use Laravel\Octane\Facades\Octane;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

/**
 * Job para despachar webhooks de renovação de subscrição
 *
 * Este job recupera a configuração de webhook ativa do utilizador,
 * monta o payload com assinatura HMAC e envia via POST assíncrono.
 * Implementa retentativas automáticas com backoff progressivo.
 */
final class DispatchWebhookJob implements ShouldQueue
{
    use Loggable;
    use Queueable;
    use Dispatchable;
    use SerializesModels;
    use InteractsWithQueue;

    /**
     * Número de tentativas em caso de falha
     */
    public int $tries = 5;

    /**
     * Timeout em segundos para cada requisição
     */
    public int $timeout = 30;

    /**
     * Backoff progressivo em segundos entre tentativas (exponencial)
     */
    public array $backoff = [60, 300, 900, 3600]; // 1min, 5min, 15min, 1h

    private string $subscriptionId;
    private string $userId;
    private string $billingHistoryId;
    private array $eventData;

    public function __construct(
        string $subscriptionId,
        string $userId,
        string $billingHistoryId,
        array $eventData,
    ) {
        $this->onQueue('webhook');

        $this->userId = $userId;
        $this->eventData = $eventData;
        $this->subscriptionId = $subscriptionId;
        $this->billingHistoryId = $billingHistoryId;
    }
    public function handle(WebhookConfigRepositoryInterface $repository): void
    {
        $this->logger()->info('Processing webhook dispatch', [
            'subscription_id' => $this->subscriptionId,
            'user_id' => $this->userId,
            'billing_history_id' => $this->billingHistoryId,
            'attempt' => $this->attempts(),
        ]);

        [$webhookConfig, $payload] = Octane::concurrently([
            fn () => $repository->findActiveByUserId(Uuid::fromString($this->userId)),
            fn () => $this->buildPayload(),
        ], 5000);

        if ($webhookConfig === null) {
            $this->logger()->info('No active webhook configuration found for user', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        // Serializa payload e gera assinatura
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $signature = $webhookConfig->secret()
            ? hash_hmac('sha256', $payloadJson, $webhookConfig->secret())
            : null;

        $this->logger()->info('Sending webhook request', [
            'webhook_url' => $webhookConfig->url()->value(),
            'user_id' => $this->userId,
            'payload_size' => strlen($payloadJson),
            'has_signature' => $signature !== null,
        ]);

        try {
            // Executa POST com timeout
            $headers = [
                'Content-Type' => 'application/json',
                'X-Event-Type' => 'subscription.renewed',
                'X-Subscription-Id' => $this->subscriptionId,
                'X-Request-Id' => Uuid::uuid4()->toString(),
            ];

            // Adiciona signature apenas se existir
            if ($signature !== null) {
                $headers['X-Hub-Signature'] = 'sha256=' . $signature;
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($webhookConfig->url()->value(), $payload);

            // Verifica resposta
            if ($response->successful()) {
                $this->logger()->info('Webhook delivered successfully', [
                    'webhook_url' => $webhookConfig->url()->value(),
                    'status_code' => $response->status(),
                    'user_id' => $this->userId,
                    'subscription_id' => $this->subscriptionId,
                    'attempt' => $this->attempts(),
                ]);
            } else {
                $this->handleFailedResponse($response, $webhookConfig->url()->value());
            }
        } catch (ConnectionException $e) {
            $this->logger()->error('Webhook connection failed', [
                'webhook_url' => $webhookConfig->url()->value(),
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        } catch (Exception $e) {
            $this->logger()->error('Webhook dispatch failed with exception', [
                'webhook_url' => $webhookConfig->url()->value(),
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Monta o payload do webhook
     */
    private function buildPayload(): array
    {
        $subscriptionName = $this->eventData['subscription_name'];

        // Suporta tanto o formato antigo (objeto Money) quanto o novo (int em centavos)
        $amountInCents = $this->eventData['amount'];
        if (is_object($amountInCents) && method_exists($amountInCents, 'toCents')) {
            $amountInCents = $amountInCents->toCents();
        }

        $amount = number_format($amountInCents / 100, 2, ',', '.');
        $currency = strtoupper($this->eventData['currency']);
        $billingDate = \DateTime::createFromFormat('Y-m-d H:i:s', $this->eventData['billing_date'])->format('d/m/Y');
        $nextBillingDate = \DateTime::createFromFormat('Y-m-d', $this->eventData['next_billing_date'])->format('d/m/Y');
        $billingCycle = $this->eventData['billing_cycle'];

        $billingCycleText = match($billingCycle) {
            'monthly' => 'mensal',
            'yearly' => 'anual',
            'weekly' => 'semanal',
            'daily' => 'diário',
            default => $billingCycle,
        };

        $message = "💰 Fatura da assinatura {$subscriptionName} processada com sucesso!\n\n" .
                   "Detalhes da cobrança:\n" .
                   "• Valor debitado: {$currency} {$amount}\n" .
                   "• Data do débito: {$billingDate}\n" .
                   "• Ciclo de cobrança: {$billingCycleText}\n" .
                   "• Próxima cobrança: {$nextBillingDate}\n\n" .
                   "✅ A data do próximo pagamento foi atualizada automaticamente.";

        return [
            'content' => $message,
            'event' => 'subscription.renewed',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'subscription' => [
                    'id' => $this->eventData['subscription_id'],
                    'name' => $this->eventData['subscription_name'],
                    'amount' => $amountInCents,
                    'amount_formatted' => "{$currency} {$amount}",
                    'currency' => $this->eventData['currency'],
                    'billing_cycle' => $this->eventData['billing_cycle'],
                    'billing_cycle_text' => $billingCycleText,
                    'next_billing_date' => $this->eventData['next_billing_date'],
                    'next_billing_date_formatted' => $nextBillingDate,
                    'status' => $this->eventData['status'] ?? 'active',
                ],
                'billing' => [
                    'id' => $this->eventData['billing_history_id'],
                    'date' => $this->eventData['billing_date'],
                    'date_formatted' => $billingDate,
                    'amount' => $amountInCents,
                    'amount_formatted' => "{$currency} {$amount}",
                    'currency' => $this->eventData['currency'],
                ],
                'user_id' => $this->eventData['user_id'],
                'message' => $message,
            ],
            'metadata' => [
                'occurred_at' => $this->eventData['occurred_at'],
                'attempt' => $this->attempts(),
                'source' => 'subscription-tracker',
                'version' => '1.0',
            ],
        ];
    }

    /**
     * Lida com resposta falha (4xx ou 5xx)
     */
    private function handleFailedResponse($response, string $url): void
    {
        $statusCode = $response->status();
        $responseBody = $response->body();

        $this->logger()->warning('Webhook returned error status', [
            'webhook_url' => $url,
            'status_code' => $statusCode,
            'response_body' => substr($responseBody, 0, 500),
            'user_id' => $this->userId,
            'subscription_id' => $this->subscriptionId,
            'attempt' => $this->attempts(),
        ]);

        if ($statusCode >= 500) {
            throw new RuntimeException(__('Subscription::exception.webhook_server_error', ['statusCode' => $statusCode]));
        } elseif ($statusCode === 429) {
            throw new RuntimeException(__('Subscription::exception.webhook_rate_limited', ['statusCode' => $statusCode]));
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            if ($this->attempts() < 3) {
                throw new RuntimeException(__('Subscription::exception.webhook_client_error', ['statusCode' => $statusCode]));
            }

            $this->logger()->error('Webhook permanently failed with client error', [
                'webhook_url' => $url,
                'status_code' => $statusCode,
                'user_id' => $this->userId,
                'final_attempt' => $this->attempts(),
            ]);
        }
    }

    /**
     * Lida com falha permanente do job
     */
    public function failed(Throwable $exception): void
    {
        $this->logger()->error('Webhook dispatch job failed permanently', [
            'subscription_id' => $this->subscriptionId,
            'user_id' => $this->userId,
            'billing_history_id' => $this->billingHistoryId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'total_attempts' => $this->attempts(),
        ]);
    }
}
