<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

final readonly class DeactivateWebhookUseCase
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository
    ) {
    }

    public function execute(string $id): WebhookConfigDTO
    {
        $this->logger()->info('Deactivating webhook config', [
            'webhook_config_id' => $id,
        ]);

        try {
            $entity = $this->repository->findById(Uuid::fromString($id));

            if (!$entity) {
                throw new \InvalidArgumentException('Webhook config not found');
            }

            $entity->deactivate();

            $this->repository->save($entity);

            $this->logger()->event('WebhookConfigDeactivated', [
                'webhook_config_id' => $id,
            ]);

            $this->logger()->audit(
                action: 'deactivate',
                entityType: 'WebhookConfig',
                entityId: $id,
                context: []
            );

            return WebhookConfigDTO::fromEntity($entity);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to deactivate webhook config', [
                'webhook_config_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
