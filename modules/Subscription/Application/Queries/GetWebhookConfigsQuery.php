<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Ramsey\Uuid\Uuid;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;

/**
 * Query para buscar todas as configurações de webhook do usuário
 */
final readonly class GetWebhookConfigsQuery
{
    use Loggable;

    public function __construct(
        private WebhookConfigRepositoryInterface $repository
    ) {
    }

    /**
     * @return WebhookConfigDTO[]
     */
    public function execute(string $userId): array
    {
        $this->logger()->debug('Finding webhook configs for user', [
            'user_id' => $userId,
        ]);

        $webhookConfigs = $this->repository->findAllByUserId(
            Uuid::fromString($userId)
        );

        return array_map(
            fn ($entity) => WebhookConfigDTO::fromEntity($entity)->toArray(),
            $webhookConfigs
        );
    }
}
