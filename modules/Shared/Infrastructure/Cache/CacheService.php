<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Cache;

use Throwable;
use Illuminate\Support\Facades\Cache;
use Modules\Shared\Domain\Contracts\LoggerInterface;
use Modules\Shared\Domain\Contracts\CacheServiceInterface;

final class CacheService implements CacheServiceInterface
{
    private const DEFAULT_TTL = 3600; // 1 hora

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $tags = [],
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $startTime = microtime(true);

        try {
            $value = $this->getCacheInstance()->get($key, $default);
            $hit = $value !== $default;

            $this->logCacheOperation('get', $key, [
                'hit' => $hit,
                'duration_ms' => $this->calculateDuration($startTime),
            ]);

            return $value;
        } catch (Throwable $e) {
            $this->logger->error('Cache get failed', ['key' => $key], $e);
            return $default;
        }
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $startTime = microtime(true);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            $result = $this->getCacheInstance()->put($key, $value, $ttl);

            $this->logCacheOperation('put', $key, [
                'ttl' => $ttl,
                'duration_ms' => $this->calculateDuration($startTime),
                'success' => $result,
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->logger->error('Cache put failed', ['key' => $key, 'ttl' => $ttl], $e);
            return false;
        }
    }

    public function forever(string $key, mixed $value): bool
    {
        $startTime = microtime(true);

        try {
            $result = $this->getCacheInstance()->forever($key, $value);

            $this->logCacheOperation('forever', $key, [
                'duration_ms' => $this->calculateDuration($startTime),
                'success' => $result,
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->logger->error('Cache forever failed', ['key' => $key], $e);
            return false;
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $startTime = microtime(true);

        try {
            $value = $this->getCacheInstance()->remember($key, $ttl, $callback);

            $this->logCacheOperation('remember', $key, [
                'ttl' => $ttl,
                'duration_ms' => $this->calculateDuration($startTime),
            ]);

            return $value;
        } catch (Throwable $e) {
            $this->logger->error('Cache remember failed', ['key' => $key, 'ttl' => $ttl], $e);
            return $callback();
        }
    }

    public function forget(string $key): bool
    {
        $startTime = microtime(true);

        try {
            $result = $this->getCacheInstance()->forget($key);

            $this->logCacheOperation('forget', $key, [
                'duration_ms' => $this->calculateDuration($startTime),
                'success' => $result,
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->logger->error('Cache forget failed', ['key' => $key], $e);
            return false;
        }
    }

    public function forgetMany(array $keys): bool
    {
        $startTime = microtime(true);

        try {
            $success = true;
            foreach ($keys as $key) {
                if (!$this->forget($key)) {
                    $success = false;
                }
            }

            $this->logCacheOperation('forgetMany', implode(',', $keys), [
                'count' => count($keys),
                'duration_ms' => $this->calculateDuration($startTime),
                'success' => $success,
            ]);

            return $success;
        } catch (Throwable $e) {
            $this->logger->error('Cache forgetMany failed', ['keys_count' => count($keys)], $e);
            return false;
        }
    }

    public function invalidateTags(array $tags): bool
    {
        $startTime = microtime(true);

        try {
            Cache::tags($tags)->flush();

            $this->logCacheOperation('invalidateTags', implode(',', $tags), [
                'tags' => $tags,
                'duration_ms' => $this->calculateDuration($startTime),
                'success' => true,
            ]);

            return true;
        } catch (Throwable $e) {
            $this->logger->error('Cache invalidateTags failed', ['tags' => $tags], $e);
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            return $this->getCacheInstance()->has($key);
        } catch (Throwable $e) {
            $this->logger->error('Cache has failed', ['key' => $key], $e);
            return false;
        }
    }

    public function flush(): bool
    {
        $startTime = microtime(true);

        try {
            $result = $this->getCacheInstance()->flush();

            $this->logger->warning('Cache flushed', [
                'tags' => $this->tags,
                'duration_ms' => $this->calculateDuration($startTime),
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->logger->error('Cache flush failed', [], $e);
            return false;
        }
    }

    public function increment(string $key, int $value = 1): int|false
    {
        try {
            return $this->getCacheInstance()->increment($key, $value);
        } catch (Throwable $e) {
            $this->logger->error('Cache increment failed', ['key' => $key, 'value' => $value], $e);
            return false;
        }
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        try {
            return $this->getCacheInstance()->decrement($key, $value);
        } catch (Throwable $e) {
            $this->logger->error('Cache decrement failed', ['key' => $key, 'value' => $value], $e);
            return false;
        }
    }

    public function many(array $keys): array
    {
        try {
            return $this->getCacheInstance()->many($keys);
        } catch (Throwable $e) {
            $this->logger->error('Cache many failed', ['keys_count' => count($keys)], $e);
            return [];
        }
    }

    public function putMany(array $values, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;

        try {
            return $this->getCacheInstance()->putMany($values, $ttl);
        } catch (Throwable $e) {
            $this->logger->error('Cache putMany failed', [
                'values_count' => count($values),
                'ttl' => $ttl,
            ], $e);
            return false;
        }
    }

    private function getCacheInstance(): mixed
    {
        if (empty($this->tags)) {
            return Cache::store();
        }

        return Cache::tags($this->tags);
    }

    private function logCacheOperation(string $operation, string $key, array $context = []): void
    {
        $this->logger->debug("Cache {$operation}", array_merge([
            'operation' => $operation,
            'key' => $key,
            'tags' => $this->tags,
        ], $context));
    }

    private function calculateDuration(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000, 2);
    }
}
