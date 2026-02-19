<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Logging;

use Throwable;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Domain\Enums\LogLevel;
use Modules\Shared\Domain\Enums\LogChannel;
use Modules\Shared\Domain\Contracts\LoggerInterface;

final class StructuredLogger implements LoggerInterface
{
    public function __construct(
        private readonly string $module,
        private readonly LogChannel $channel = LogChannel::APPLICATION
    ) {
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function error(string $message, array $context = [], ?Throwable $exception = null): void
    {
        if ($exception !== null) {
            $context['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = [], ?Throwable $exception = null): void
    {
        if ($exception !== null) {
            $context['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function event(string $eventName, array $context = []): void
    {
        $this->log(
            LogLevel::INFO, // Eventos usam nível INFO
            "Domain Event: {$eventName}",
            array_merge($context, [
                'event_name' => $eventName,
                'event_type' => 'domain_event',
            ])
        );
    }

    public function audit(string $action, string $entityType, string $entityId, array $context = []): void
    {
        $this->log(
            LogLevel::INFO, // Audit usa nível INFO
            "Audit: {$action} on {$entityType}",
            array_merge($context, [
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'audit_type' => 'audit',
                'user_id' => $context['user_id'] ?? 'system',
                'ip' => request()?->ip() ?? 'cli',
                'user_agent' => request()?->userAgent() ?? 'cli',
            ])
        );
    }

    private function log(LogLevel $level, string $message, array $context = []): void
    {
        $structuredContext = $this->buildStructuredContext($level, $context);

        // Mapeia LogLevel para métodos do Monolog
        $monologLevel = match($level) {
            LogLevel::DEBUG => 'debug',
            LogLevel::INFO, LogLevel::EVENT, LogLevel::AUDIT => 'info',
            LogLevel::WARNING => 'warning',
            LogLevel::ERROR => 'error',
            LogLevel::CRITICAL => 'critical',
        };

        Log::channel($this->channel->value)->{$monologLevel}($message, $structuredContext);
    }

    private function buildStructuredContext(LogLevel $level, array $context): array
    {
        return array_merge([
            'timestamp' => now()->toIso8601String(),
            'module' => $this->module,
            'channel' => $this->channel->value,
            'level' => $level->value,
            'environment' => app()->environment(),
            'request_id' => request()?->header('X-Request-ID') ?? uniqid('req_', true),
        ], $context);
    }
}
