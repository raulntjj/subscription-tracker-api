<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Throwable;
use Ramsey\Uuid\Uuid;
use InvalidArgumentException;
use Illuminate\Support\Facades\Http;
use Modules\Subscription\Application\Jobs\TestWebhookJob;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

final readonly class TestWebhookUseCase
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository,
    ) {
    }

    /**
     * Executa o teste de webhook
     *
     * @param string $id ID do webhook config
     * @param bool $async Se true, despacha para fila RabbitMQ; se false, executa sincronamente
     * @return array Resultado do teste (síncrono) ou confirmação de despacho (assíncrono)
     */
    public function execute(string $id, bool $async = false): array
    {
        $this->logger()->info('Testing webhook config', [
            'webhook_config_id' => $id,
            'async' => $async,
        ]);

        // Se assíncrono, despacha para fila RabbitMQ
        if ($async) {
            return $this->executeAsync($id);
        }

        // Execução síncrona (comportamento original)
        return $this->executeSync($id);
    }

    /**
     * Executa o teste assincronamente via RabbitMQ
     */
    private function executeAsync(string $id): array
    {
        try {
            // Valida se o webhook existe antes de despachar
            $entity = $this->repository->findById(Uuid::fromString($id));

            if (!$entity) {
                throw new InvalidArgumentException(__('Subscription::exception.webhook_config_not_found'));
            }

            // Despacha job para fila RabbitMQ
            TestWebhookJob::dispatch($id);

            $this->logger()->info('Webhook test job dispatched to queue', [
                'webhook_config_id' => $id,
                'queue' => 'webhook',
            ]);

            return [
                'dispatched' => true,
                'queue' => 'webhook',
                'webhook_config_id' => $id,
                'message' => 'Webhook test dispatched to RabbitMQ queue. Check logs for results.',
            ];
        } catch (Throwable $e) {
            $this->logger()->error('Failed to dispatch webhook test job', [
                'webhook_config_id' => $id,
            ], $e);

            throw $e;
        }
    }

    /**
     * Executa o teste sincronamente
     */
    private function executeSync(string $id): array
    {
        try {
            $entity = $this->repository->findById(Uuid::fromString($id));

            if (!$entity) {
                throw new InvalidArgumentException('Webhook config not found');
            }

            // Payload de teste
            $payload = [
                'content' => 'This is a test webhook payload',
                'event' => 'webhook.test',
                'timestamp' => now()->toIso8601String(),
                'test' => true,
                'data' => [
                    'message' => 'This is a test webhook',
                    'webhook_id' => $entity->id()->toString(),
                ],
            ];

            $payloadJson = json_encode($payload);

            $headers = [
                'Content-Type' => 'application/json',
                'X-Event-Type' => 'webhook.test',
                'X-Webhook-Id' => $entity->id()->toString(),
            ];

            // Adiciona signature apenas se secret existir
            if ($entity->secret() !== null) {
                $signature = hash_hmac('sha256', $payloadJson, $entity->secret());
                $headers['X-Hub-Signature'] = 'sha256=' . $signature;
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($entity->url(), $payload);

            $success = $response->successful();
            $statusCode = $response->status();
            $responseBody = $response->body();

            $this->logger()->info('Webhook test completed', [
                'webhook_config_id' => $id,
                'status_code' => $statusCode,
                'success' => $success,
            ]);

            return [
                'success' => $success,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'request_payload' => $payload,
            ];
        } catch (Throwable $e) {
            $this->logger()->error('Failed to test webhook config', [
                'webhook_config_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
