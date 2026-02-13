<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Jobs;

use Ramsey\Uuid\Uuid;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Shared\Infrastructure\Logging\StructuredLogger;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\WebhookConfigModel;

/**
 * Job para despachar webhooks de renovação de subscrição
 * 
 * Este job recupera a configuração de webhook ativa do utilizador,
 * monta o payload com assinatura HMAC e envia via POST assíncrono.
 * Implementa retentativas automáticas com backoff progressivo.
 */
final class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        array $eventData
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->userId = $userId;
        $this->billingHistoryId = $billingHistoryId;
        $this->eventData = $eventData;
        $this->onQueue('webhooks');
    }

    /**
     * Executa o job
     */
    public function handle(StructuredLogger $logger): void
    {
        $logger->info('Processing webhook dispatch', [
            'subscription_id' => $this->subscriptionId,
            'user_id' => $this->userId,
            'billing_history_id' => $this->billingHistoryId,
            'attempt' => $this->attempts(),
        ]);

        // 1. Recuperar configuração de webhook ativa do utilizador
        $webhookConfig = WebhookConfigModel::where('user_id', $this->userId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if ($webhookConfig === null) {
            $logger->info('No active webhook configuration found for user', [
                'user_id' => $this->userId,
            ]);
            return; // Não falhar o job, apenas não processar
        }

        // 2. Montar payload JSON
        $payload = $this->buildPayload();
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 3. Gerar assinatura HMAC SHA256 (apenas se secret existir)
        $signature = $webhookConfig->secret 
            ? hash_hmac('sha256', $payloadJson, $webhookConfig->secret)
            : null;

        $logger->info('Sending webhook request', [
            'webhook_url' => $webhookConfig->url,
            'user_id' => $this->userId,
            'payload_size' => strlen($payloadJson),
            'has_signature' => $signature !== null,
        ]);

        try {
            // 4. Executar POST assíncrono com timeout
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
                ->post($webhookConfig->url, $payload);

            // 5. Verificar resposta
            if ($response->successful()) {
                $logger->info('Webhook delivered successfully', [
                    'webhook_url' => $webhookConfig->url,
                    'status_code' => $response->status(),
                    'user_id' => $this->userId,
                    'subscription_id' => $this->subscriptionId,
                    'attempt' => $this->attempts(),
                ]);
            } else {
                // Resposta não bem-sucedida (4xx ou 5xx)
                $this->handleFailedResponse($response, $webhookConfig->url, $logger);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Erro de conexão (timeout, DNS, etc)
            $logger->error('Webhook connection failed', [
                'webhook_url' => $webhookConfig->url,
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
            ]);

            // Retentar automaticamente
            throw $e;
        } catch (\Exception $e) {
            // Outros erros
            $logger->error('Webhook dispatch failed with exception', [
                'webhook_url' => $webhookConfig->url,
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
        return [
            'event' => 'subscription.renewed',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'subscription' => [
                    'id' => $this->eventData['subscription_id'],
                    'name' => $this->eventData['subscription_name'],
                    'amount' => $this->eventData['amount'],
                    'currency' => $this->eventData['currency'],
                    'next_billing_date' => $this->eventData['next_billing_date'],
                ],
                'billing' => [
                    'id' => $this->eventData['billing_history_id'],
                    'date' => $this->eventData['billing_date'],
                    'amount' => $this->eventData['amount'],
                    'currency' => $this->eventData['currency'],
                ],
                'user_id' => $this->eventData['user_id'],
            ],
            'metadata' => [
                'occurred_at' => $this->eventData['occurred_at'],
                'attempt' => $this->attempts(),
            ],
        ];
    }

    /**
     * Lida com resposta falha (4xx ou 5xx)
     */
    private function handleFailedResponse($response, string $url, StructuredLogger $logger): void
    {
        $statusCode = $response->status();
        $responseBody = $response->body();

        $logger->warning('Webhook returned error status', [
            'webhook_url' => $url,
            'status_code' => $statusCode,
            'response_body' => substr($responseBody, 0, 500), // Limitar tamanho do log
            'user_id' => $this->userId,
            'subscription_id' => $this->subscriptionId,
            'attempt' => $this->attempts(),
        ]);

        // Decidir se deve retentar baseado no status code
        if ($statusCode >= 500) {
            // Erros de servidor (5xx) - retentar
            throw new \RuntimeException("Webhook returned server error: {$statusCode}");
        } elseif ($statusCode === 429) {
            // Rate limit - retentar
            throw new \RuntimeException("Webhook rate limited: {$statusCode}");
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            // Erros de cliente (4xx) - geralmente não retentar (exceto 429)
            // Mas vamos retentar para dar chance de correção
            if ($this->attempts() < 3) {
                throw new \RuntimeException("Webhook returned client error: {$statusCode}");
            }
            
            $logger->error('Webhook permanently failed with client error', [
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
    public function failed(\Throwable $exception): void
    {
        $logger = app(StructuredLogger::class);

        $logger->error('Webhook dispatch job failed permanently', [
            'subscription_id' => $this->subscriptionId,
            'user_id' => $this->userId,
            'billing_history_id' => $this->billingHistoryId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'total_attempts' => $this->attempts(),
        ]);

        // Aqui você pode implementar lógica adicional como:
        // - Notificar o utilizador por email
        // - Desativar o webhook automaticamente após muitas falhas
        // - Registrar em tabela de webhooks falhos para retry manual
    }
}
