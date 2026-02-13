<?php

declare(strict_types=1);

namespace Modules\User\Application\Queries;

use Illuminate\Support\Facades\DB;
use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Infrastructure\Cache\Concerns\Cacheable;

/**
 * Query para buscar um usuário por ID
 */
final readonly class FindUserByIdQuery
{
    use Loggable;
    use Cacheable;

    private const CACHE_TTL = 3600; // 1 hora

    protected function cacheTags(): array
    {
        return ['users'];
    }

    public function execute(string $userId): ?UserDTO
    {
        $startTime = microtime(true);
        $cacheKey = "user:{$userId}";

        $this->logger()->debug('Finding user by ID', [
            'user_id' => $userId,
        ]);

        // Usa CacheService com remember para buscar ou popular cache
        $userData = $this->cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($userId, $startTime) {
                $this->logger()->debug('Cache miss - fetching from database', [
                    'user_id' => $userId,
                ]);

                $userData = DB::table('users')
                    ->where('id', $userId)
                    ->first();

                if ($userData !== null) {
                    $duration = microtime(true) - $startTime;

                    $this->logger()->info('User found in database', [
                        'user_id' => $userId,
                        'cache_hit' => false,
                        'cache_populated' => true,
                        'duration_ms' => round($duration * 1000, 2),
                    ]);

                    return (array) $userData;
                }

                $duration = microtime(true) - $startTime;

                $this->logger()->warning('User not found', [
                    'user_id' => $userId,
                    'duration_ms' => round($duration * 1000, 2),
                ]);

                return null;
            }
        );

        if ($userData === null) {
            return null;
        }

        // Se veio do cache, loga
        if (is_array($userData)) {
            $duration = microtime(true) - $startTime;

            $this->logger()->info('User found in cache', [
                'user_id' => $userId,
                'cache_hit' => true,
                'duration_ms' => round($duration * 1000, 2),
            ]);
        }

        return UserDTO::fromDatabase($userData);
    }
}
