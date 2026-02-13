<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\WebhookConfigModel;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar todas as configurações de webhook do usuário
 */
final readonly class GetWebhookConfigsQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 300; // 5 minutos

    protected function cacheTags(): array
    {
        return ['webhook_configs'];
    }

    public function execute(string $userId): array
    {
        $startTime = microtime(true);
        $cacheKey = "webhook_configs:user:{$userId}";

        $this->logger()->debug('Finding webhook configs for user', [
            'user_id' => $userId,
        ]);

        $result = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($userId) {
                $models = WebhookConfigModel::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->get();

                return $models->map(function ($model) {
                    return WebhookConfigDTO::fromArray([
                        'id' => $model->id,
                        'user_id' => $model->user_id,
                        'url' => $model->url,
                        'is_active' => $model->is_active,
                        'created_at' => $model->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $model->updated_at->format('Y-m-d H:i:s'),
                    ]);
                })->all();
            }
        );

        $duration = microtime(true) - $startTime;

        $this->logger()->info('Webhook configs returned', [
            'user_id' => $userId,
            'count' => count($result),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return $result;
    }
}
