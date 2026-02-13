<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

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
            $entity = $this->repository->findById(Uuid::fromString($id));

            if (!$entity) {
                throw new \InvalidArgumentException('Webhook config not found');
            }

            $entity->activate();

            $this->repository->save($entity);

            $this->logger()->event('WebhookConfigActivated', [
                'webhook_config_id' => $id,
            ]);

            $this->logger()->audit(
                action: 'activate',
                entityType: 'WebhookConfig',
                entityId: $id,
                context: []
            );

            return WebhookConfigDTO::fromArray([
                'id' => $entity->id()->toString(),
                'user_id' => $entity->userId()->toString(),
                'url' => $entity->url(),
                'is_active' => $entity->isActive(),
                'created_at' => $entity->createdAt()->format('Y-m-d H:i:s'),
                'updated_at' => $entity->updatedAt()?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to activate webhook config', [
                'webhook_config_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
