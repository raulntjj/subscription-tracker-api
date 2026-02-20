<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\UseCases;

use Throwable;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Modules\Subscription\Domain\Entities\WebhookConfig;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Application\DTOs\CreateWebhookConfigDTO;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

final readonly class CreateWebhookConfigUseCase
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository,
    ) {
    }

    public function execute(CreateWebhookConfigDTO $dto): WebhookConfigDTO
    {
        $this->logger()->info('Creating new webhook config', [
            'url' => $dto->url,
            'user_id' => $dto->userId,
        ]);

        try {
            $entity = new WebhookConfig(
                id: Uuid::uuid4(),
                userId: Uuid::fromString($dto->userId),
                url: $dto->url,
                secret: $dto->secret,
                isActive: true,
                createdAt: new DateTimeImmutable(),
                updatedAt: new DateTimeImmutable(),
            );

            $this->repository->save($entity);

            $this->logger()->event('WebhookConfigCreated', [
                'webhook_config_id' => $entity->id()->toString(),
                'user_id' => $dto->userId,
            ]);

            $this->logger()->audit(
                action: 'create',
                entityType: 'WebhookConfig',
                entityId: $entity->id()->toString(),
                context: [
                    'url' => $entity->url(),
                    'is_active' => $entity->isActive(),
                ],
            );

            return WebhookConfigDTO::fromEntity($entity);
        } catch (Throwable $e) {
            $this->logger()->error('Failed to create webhook config', [
                'url' => $dto->url,
            ], $e);

            throw $e;
        }
    }
}
