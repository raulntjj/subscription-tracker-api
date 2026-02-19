<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Logging;

use Modules\Shared\Domain\Enums\LogChannel;
use Modules\Shared\Domain\Contracts\LoggerInterface;

final class LoggerFactory
{
    /**
     * Cria um logger para um módulo específico
     */
    public static function forModule(string $moduleName, LogChannel $channel = LogChannel::APPLICATION): LoggerInterface
    {
        return new StructuredLogger($moduleName, $channel);
    }

    /**
     * Cria um logger para domínio (eventos e regras de negócio)
     */
    public static function forDomain(string $moduleName): LoggerInterface
    {
        return new StructuredLogger($moduleName, LogChannel::DOMAIN);
    }

    /**
     * Cria um logger para infraestrutura (repositórios, cache, etc)
     */
    public static function forInfrastructure(string $moduleName): LoggerInterface
    {
        return new StructuredLogger($moduleName, LogChannel::INFRASTRUCTURE);
    }

    /**
     * Cria um logger para auditoria
     */
    public static function forAudit(string $moduleName): LoggerInterface
    {
        return new StructuredLogger($moduleName, LogChannel::AUDIT);
    }

    /**
     * Cria um logger para segurança
     */
    public static function forSecurity(string $moduleName): LoggerInterface
    {
        return new StructuredLogger($moduleName, LogChannel::SECURITY);
    }

    /**
     * Cria um logger para performance
     */
    public static function forPerformance(string $moduleName): LoggerInterface
    {
        return new StructuredLogger($moduleName, LogChannel::PERFORMANCE);
    }
}
