<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Cache\Concerns;

use Modules\Shared\Domain\Contracts\CacheServiceInterface;
use Modules\Shared\Infrastructure\Cache\CacheServiceFactory;
use Modules\Shared\Infrastructure\Concerns\ModuleAware;

trait Cacheable
{
    use ModuleAware;

    /**
     * Tags de cache para invalidação.
     * Sobrescreva este método para usar tags específicas
     * que sejam consistentes com o repositório do módulo.
     *
     * @return array<string>
     */
    protected function cacheTags(): array
    {
        $className = static::class;
        $moduleName = $this->extractModuleName($className);

        return [strtolower($moduleName)];
    }

    protected function cache(): CacheServiceInterface
    {
        return CacheServiceFactory::withTags($this->cacheTags());
    }
}
