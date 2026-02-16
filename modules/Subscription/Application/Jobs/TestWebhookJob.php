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
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

/**
 * Job para testar webhook via RabbitMQ
 *
 * Este job é despachado para a fila 'webhooks' e executa o teste
 * de webhook de forma assíncrona, retornando os resultados via log.
 */
final class TestWebhookJob implements ShouldQueue
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
    public int $timeout = 30;

    private string $webhookConfigId;

    public function __construct(string $webhookConfigId)
    {
        $this->webhookConfigId = $webhookConfigId;
        $this->onQueue('webhooks');
    }

    /**
     * Executa o job
     */
    public function handle(
        WebhookConfigRepositoryInterface $repository
    ): void {
        $logger = new StructuredLogger('Subscription');

        $logger->info('Starting webhook test job', [
            'webhook_config_id' => $this->webhookConfigId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $entity = $repository->findById(Uuid::fromString($this->webhookConfigId));

            if (!$entity) {
                $logger->error('Webhook config not found', [
                    'webhook_config_id' => $this->webhookConfigId,
                ]);
                $this->fail(new \InvalidArgumentException('Webhook config not found'));
                return;
            }

            // Payload de teste
            $payload = [
                'content' => 'This is a test webhook payload sent via RabbitMQ',
                'event' => 'webhook.test',
                'timestamp' => now()->toIso8601String(),
                'test' => true,
                'queue_info' => [
                    'attempt' => $this->attempts(),
                    'queue' => $this->queue,
                    'job_id' => $this->job?->getJobId(),
                ],
                'data' => [
                    'message' => 'This is a test webhook dispatched via RabbitMQ queue',
                    'webhook_id' => $entity->id()->toString(),
                ],
            ];

            $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $headers = [
                'Content-Type' => 'application/json',
                'X-Event-Type' => 'webhook.test',
                'X-Webhook-Id' => $entity->id()->toString(),
                'X-Queue-Job' => 'true',
                'X-Attempt' => (string) $this->attempts(),
            ];

            // Adiciona signature apenas se secret existir
            if ($entity->secret() !== null) {
                $signature = hash_hmac('sha256', $payloadJson, $entity->secret());
                $headers['X-Hub-Signature'] = 'sha256=' . $signature;
            }

            $logger->info('Sending test webhook request', [
                'webhook_config_id' => $this->webhookConfigId,
                'url' => $entity->url(),
                'has_signature' => $entity->secret() !== null,
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($entity->url(), $payload);

            $success = $response->successful();
            $statusCode = $response->status();
            $responseBody = $response->body();

            if ($success) {
                $logger->info('Webhook test completed successfully', [
                    'webhook_config_id' => $this->webhookConfigId,
                    'status_code' => $statusCode,
                    'attempt' => $this->attempts(),
                ]);
            } else {
                $logger->warning('Webhook test returned non-success status', [
                    'webhook_config_id' => $this->webhookConfigId,
                    'status_code' => $statusCode,
                    'response_body' => substr($responseBody, 0, 500),
                    'attempt' => $this->attempts(),
                ]);

                // Se não for sucesso e ainda tiver tentativas, lança exceção para retry
                if ($this->attempts() < $this->tries) {
                    throw new \RuntimeException("Webhook returned status {$statusCode}");
                }
            }
        } catch (\Throwable $e) {
            $logger->error('Failed to test webhook', [
                'webhook_config_id' => $this->webhookConfigId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ], $e);

            // Re-lança a exceção para que o Laravel tente novamente
            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            // Se esgotar as tentativas, falha permanentemente
            $this->fail($e);
        }
    }

    /**
     * Lida com falha do job
     */
    public function failed(\Throwable $exception): void
    {
        $logger = new StructuredLogger('Subscription');

        $logger->error('Webhook test job failed permanently', [
            'webhook_config_id' => $this->webhookConfigId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ], $exception);
    }

    /**
     * Obtém tags para monitoramento
     */
    public function tags(): array
    {
        return [
            'webhook:test',
            "webhook:{$this->webhookConfigId}",
            'queue:webhooks',
        ];
    }
}
