<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;
use Throwable;

final readonly class ActivateWebhookUseCase
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository
    ) {
    }

    public function execute(string $id): WebhookConfigDTO
    {
        $this->logger()->info('Activating webhook config', [
            'webhook_config_id' => $id,
        ]);

        try {
            $webhook = $this->repository->findById(Uuid::fromString($id));

            if (!$webhook) {
                throw new \InvalidArgumentException('Webhook config not found');
            }

            $webhook->activate();

            $this->repository->save($webhook);

            $this->logger()->event('WebhookConfigActivated', [
                'webhook_config_id' => $id,
            ]);

            $this->logger()->audit(
                action: 'activate',
                entityType: 'WebhookConfig',
                entityId: $id,
                context: []
            );

            return WebhookConfigDTO::fromEntity($webhook);
        } catch (Throwable $e) {
            $this->logger()->error('Failed to activate webhook config', [
                'webhook_config_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
