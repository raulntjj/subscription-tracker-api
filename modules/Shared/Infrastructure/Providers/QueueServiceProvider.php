<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Providers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

final class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerQueueMonitoring();
    }

    /**
     * Registra listeners para monitorar filas do RabbitMQ via Redis
     *
     * Armazena informações sobre jobs em tempo real usando Redis (conexão 'sessions')
     * - Jobs em processamento: armazenados em hash "queue_monitor:{job_id}"
     * - Lista de jobs ativos: set "queue_monitor:active"
     * - Lista de jobs concluídos: set "queue_monitor:completed"
     * - Lista de jobs falhados: set "queue_monitor:failed"
     *
     * TTL:
     * - Jobs concluídos: 1 hora (3600s)
     * - Jobs falhados: 24 horas (86400s)
     */
    private function registerQueueMonitoring(): void
    {
        // Quando um job começa a processar
        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event): void {
            $id = $event->job->getJobId();
            $connection = Redis::connection('sessions');

            $connection->hset("queue_monitor:$id", [
                'job' => $event->job->resolveName(),
                'queue' => $event->job->getQueue(),
                'status' => 'processing',
                'attempts' => $event->job->attempts(),
                'started_at' => now()->toDateTimeString(),
                'payload' => json_encode($event->job->payload()),
            ]);

            // Adiciona o ID à lista de jobs ativos
            $connection->sadd('queue_monitor:active', $id);
        });

        // Quando um job termina com sucesso
        $this->app['events']->listen(JobProcessed::class, function (JobProcessed $event): void {
            $id = $event->job->getJobId();
            $connection = Redis::connection('sessions');

            $connection->hmset("queue_monitor:$id", [
                'status' => 'completed',
                'finished_at' => now()->toDateTimeString(),
            ]);

            // Remove da lista de ativos e adiciona à lista de concluídos
            $connection->srem('queue_monitor:active', $id);
            $connection->sadd('queue_monitor:completed', $id);

            // Expira o log em 1 hora para não encher o Redis
            $connection->expire("queue_monitor:$id", 3600);
        });

        // Quando um job falha
        $this->app['events']->listen(JobFailed::class, function (JobFailed $event): void {
            $id = $event->job->getJobId();
            $connection = Redis::connection('sessions');

            $connection->hmset("queue_monitor:$id", [
                'status' => 'failed',
                'error_message' => $event->exception->getMessage(),
                'error_trace' => $event->exception->getTraceAsString(),
                'failed_at' => now()->toDateTimeString(),
            ]);

            // Remove da lista de ativos e adiciona à lista de falhados
            $connection->srem('queue_monitor:active', $id);
            $connection->sadd('queue_monitor:failed', $id);

            // Mantém erros por 24h
            $connection->expire("queue_monitor:$id", 86400);
        });
    }
}
