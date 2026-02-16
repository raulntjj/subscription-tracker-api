<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestRabbitMQJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $message
    ) {
    }

    public function handle(): void
    {
        Log::info('RabbitMQ Job Executado!', [
            'message' => $this->message,
            'timestamp' => now()->toDateTimeString(),
            'queue' => $this->queue ?? 'default',
            'attempts' => $this->attempts(),
        ]);

        // Simula algum processamento
        sleep(2);

        Log::info('RabbitMQ Job Finalizado!', [
            'message' => $this->message,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RabbitMQ Job Falhou!', [
            'message' => $this->message,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
