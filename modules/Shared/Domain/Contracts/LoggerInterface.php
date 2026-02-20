<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Contracts;

use Throwable;

interface LoggerInterface
{
    /**
     * Log de informação (operações normais)
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log de debug (desenvolvimento)
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log de warning (situações anormais mas não críticas)
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log de erro (falhas que precisam atenção)
     */
    public function error(string $message, array $context = [], ?Throwable $exception = null): void;

    /**
     * Log crítico (sistema pode estar comprometido)
     */
    public function critical(string $message, array $context = [], ?Throwable $exception = null): void;

    /**
     * Log de evento de negócio (domain events)
     */
    public function event(string $eventName, array $context = []): void;

    /**
     * Log de audit (rastreabilidade de ações)
     */
    public function audit(string $action, string $entityType, string $entityId, array $context = []): void;
}
