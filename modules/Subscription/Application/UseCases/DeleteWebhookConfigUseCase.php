<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class DeleteWebhookConfigUseCase
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository
    ) {
    }

    public function execute(string $id): void
    {
        $this->logger()->info('Deleting webhook config', [
            'webhook_config_id' => $id,
        ]);

        try {
            $entity = $this->repository->findById(Uuid::fromString($id));

            if (!$entity) {
                throw new \InvalidArgumentException('Webhook config not found');
            }

            $this->repository->delete(Uuid::fromString($id));

            $this->logger()->event('WebhookConfigDeleted', [
                'webhook_config_id' => $id,
            ]);

            $this->logger()->audit(
                action: 'delete',
                entityType: 'WebhookConfig',
                entityId: $id,
                context: []
            );
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to delete webhook config', [
                'webhook_config_id' => $id,
            ], $e);

            throw $e;
        }
    }
}
