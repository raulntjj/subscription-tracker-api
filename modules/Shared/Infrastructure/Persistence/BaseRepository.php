<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Domain\Contracts\CacheServiceInterface;
use Modules\Shared\Infrastructure\Cache\CacheServiceFactory;

abstract class BaseRepository
{
    protected readonly CacheServiceInterface $cache;

    public function __construct()
    {
        $this->cache = CacheServiceFactory::withTags($this->getCacheTags());
    }

    /**
     * Nome da tag de cache para invalidação
     */
    abstract protected function getCacheTags(): array;

    /**
     * Executa uma operação dentro de uma transação e invalida o cache
     */
    protected function executeInTransaction(callable $operation): void
    {
        DB::transaction(function () use ($operation) {
            $operation();
        });

        $this->invalidateCache();
    }

    /**
     * Invalida o cache baseado nas tags configuradas
     */
    protected function invalidateCache(): void
    {
        $tags = $this->getCacheTags();

        if (!empty($tags)) {
            $this->cache->invalidateTags($tags);
        }
    }

    /**
     * Salva (cria ou atualiza) um modelo e invalida o cache
     */
    protected function saveModel(Model $model): void
    {
        $this->executeInTransaction(function () use ($model) {
            $model->save();
        });
    }

    /**
     * Deleta um modelo e invalida o cache
     */
    protected function deleteModel(Model $model): void
    {
        $this->executeInTransaction(function () use ($model) {
            $model->delete();
        });
    }

    /**
     * Cria ou atualiza um registro usando updateOrCreate e invalida o cache
     */
    protected function upsert(string $modelClass, array $attributes, array $values): Model
    {
        $model = null;

        $this->executeInTransaction(function () use ($modelClass, $attributes, $values, &$model) {
            $model = $modelClass::updateOrCreate($attributes, $values);
        });

        return $model;
    }

    /**
     * Busca um registro em cache ou no banco de dados
     */
    protected function findWithCache(
        string $cacheKey,
        callable $fetchFromDatabase,
        int $ttl = 3600
    ): mixed {
        return $this->cache->remember($cacheKey, $ttl, $fetchFromDatabase);
    }

    /**
     * Remove um registro do cache específico
     */
    protected function removeCacheKey(string $cacheKey): void
    {
        $this->cache->forget($cacheKey);
    }
}
