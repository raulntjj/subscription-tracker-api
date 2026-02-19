<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\WebhookConfigModel;

/**
 * Query para buscar uma configuração de webhook específica
 */
final readonly class GetWebhookConfigByIdQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 300; // 5 minutos

    protected function cacheTags(): array
    {
        return ['webhook_configs'];
    }

    public function execute(string $id, string $userId): ?WebhookConfigDTO
    {
        $cacheKey = "webhook_config:id:{$id}";

        $this->logger()->debug('Finding webhook config by ID', [
            'webhook_config_id' => $id,
            'user_id' => $userId,
        ]);

        return $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($id, $userId) {
                $model = WebhookConfigModel::where('id', $id)
                    ->where('user_id', $userId)
                    ->first();

                if (!$model) {
                    return null;
                }

                return WebhookConfigDTO::fromArray([
                    'id' => $model->id,
                    'user_id' => $model->user_id,
                    'url' => $model->url,
                    'is_active' => $model->is_active,
                    'created_at' => $model->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $model->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
        );
    }
}
