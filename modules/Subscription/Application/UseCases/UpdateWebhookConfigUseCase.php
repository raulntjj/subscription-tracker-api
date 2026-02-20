<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Application\DTOs\UpdateWebhookConfigDTO;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

final readonly class UpdateWebhookConfigUseCase
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository
    ) {
    }

    public function execute(UpdateWebhookConfigDTO $dto): WebhookConfigDTO
    {
        $this->logger()->info('Updating webhook config', [
            'webhook_config_id' => $dto->id,
        ]);

        try {
            $entity = $this->repository->findById(Uuid::fromString($dto->id));

            if (!$entity) {
                throw new \InvalidArgumentException('Webhook config not found');
            }

            if ($dto->url !== null) {
                $entity->changeUrl($dto->url);
            }

            if ($dto->secret !== null) {
                $entity->changeSecret($dto->secret);
            }

            $this->repository->save($entity);

            $this->logger()->event('WebhookConfigUpdated', [
                'webhook_config_id' => $entity->id()->toString(),
            ]);

            $this->logger()->audit(
                action: 'update',
                entityType: 'WebhookConfig',
                entityId: $entity->id()->toString(),
                context: [
                    'url' => $entity->url(),
                    'url_updated' => $dto->url !== null,
                    'secret_updated' => $dto->secret !== null,
                ]
            );

            return WebhookConfigDTO::fromEntity($entity);
        } catch (\Throwable $e) {
            $this->logger()->error('Failed to update webhook config', [
                'webhook_config_id' => $dto->id,
            ], $e);

            throw $e;
        }
    }
}
