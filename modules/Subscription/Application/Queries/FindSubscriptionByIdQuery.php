<?php

declare(strict_types=1);

namespace Modules\Subscription\Application\Queries;

use Illuminate\Support\Facades\DB;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

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
        $startTime = microtime(true);
        $cacheKey = "subscription:{$id}";

        $this->logger()->debug('Finding subscription by ID', [
            'subscription_id' => $id,
        ]);

        $data = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($id, $startTime) {
                $this->logger()->debug('Cache miss - fetching from database', [
                    'subscription_id' => $id,
                ]);

                $data = DB::table('subscriptions')
                    ->where('id', $id)
                    ->first();

                if ($data !== null) {
                    $duration = microtime(true) - $startTime;

                    $this->logger()->info('Subscription found in database', [
                        'subscription_id' => $id,
                        'cache_hit' => false,
                        'duration_ms' => round($duration * 1000, 2),
                    ]);

                    return (array) $data;
                }

                $this->logger()->warning('Subscription not found', [
                    'subscription_id' => $id,
                ]);

                return null;
            }
        );

        if ($data === null) {
            return null;
        }

        return SubscriptionDTO::fromDatabase($data);
    }
}
