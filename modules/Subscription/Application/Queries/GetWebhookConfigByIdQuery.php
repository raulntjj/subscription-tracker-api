<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

/**
 * Query para buscar uma configuração de webhook específica
 */
final readonly class GetWebhookConfigByIdQuery
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository,
    ) {
    }

    public function execute(string $id, string $userId): ?WebhookConfigDTO
    {
        $this->logger()->debug('Finding webhook config by ID', [
            'webhook_config_id' => $id,
            'user_id' => $userId,
        ]);

        $webhookConfig = $this->repository->findByIdAndUserId(
            Uuid::fromString($id),
            Uuid::fromString($userId),
        );

        if ($webhookConfig === null) {
            return null;
        }

        return WebhookConfigDTO::fromEntity($webhookConfig);
    }
}
