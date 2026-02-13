<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Cache;

use Modules\Shared\Domain\Contracts\CacheServiceInterface;
use Modules\Shared\Infrastructure\Logging\LoggerFactory;

final class CacheServiceFactory
{
    /**
     * Cria um CacheService sem tags
     */
    public static function create(): CacheServiceInterface
    {
        $logger = LoggerFactory::forInfrastructure('Cache');
        return new CacheService($logger);
    }

    /**
     * Cria um CacheService com tags específicas
     */
    public static function withTags(array $tags): CacheServiceInterface
    {
        $logger = LoggerFactory::forInfrastructure('Cache');
        return new CacheService($logger, $tags);
    }

    /**
     * Cria um CacheService para um módulo específico
     */
    public static function forModule(string $moduleName): CacheServiceInterface
    {
        $logger = LoggerFactory::forInfrastructure($moduleName);
        $tags = [strtolower($moduleName)];

        return new CacheService($logger, $tags);
    }
}
