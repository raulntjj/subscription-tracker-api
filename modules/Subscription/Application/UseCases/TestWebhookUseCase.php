<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Http;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class TestWebhookUseCase
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository
    ) {
    }

    public function execute(string $id): array
    {
        $this->logger()->info('Testing webhook config', [
            'webhook_config_id' => $id,
        ]);

        try {
            $entity = $this->repository->findById(Uuid::fromString($id));

            if (!$entity) {
                throw new \InvalidArgumentException('Webhook config not found');
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
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to test webhook config', [
                'webhook_config_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
