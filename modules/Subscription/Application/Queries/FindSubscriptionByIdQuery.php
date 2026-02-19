<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Subscription\Infrastructure\Persistence\Eloquent\SubscriptionModel;

/**
 * Query para buscar um subscription por ID
 */
final readonly class FindSubscriptionByIdQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 3600; // 1 hora

    protected function cacheTags(): array
    {
        return ['subscriptions'];
    }

    public function execute(string $id): ?SubscriptionDTO
    {
        $cacheKey = "subscription:{$id}";

        $this->logger()->debug('Finding subscription by ID', [
            'subscription_id' => $id,
        ]);

        $data = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($id) {
                return SubscriptionModel::query()
                    ->where('id', $id)
                    ->first();
            }
        );

        if ($data === null) {
            return null;
        }

        return SubscriptionDTO::fromDatabase($data);
    }
}
