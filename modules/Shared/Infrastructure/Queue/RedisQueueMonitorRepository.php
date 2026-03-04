<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Queue;

use Carbon\Carbon;
use Laravel\Octane\Facades\Octane;
use Illuminate\Support\Facades\Redis;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class RedisQueueMonitorRepository implements QueueMonitorRepositoryInterface
{
    private const PREFIX = 'queue_monitor:';

    public function __construct(
        private readonly string $connection = 'sessions',
    ) {
    }

    public function getActiveJobs(): array
    {
        $connection = Redis::connection($this->connection);
        $activeJobIds = $connection->smembers(self::PREFIX . 'active');

        return $this->getJobsDetails($activeJobIds);
    }

    public function getCompletedJobs(int $limit = 10): array
    {
        $connection = Redis::connection($this->connection);
        $completedJobIds = $connection->smembers(self::PREFIX . 'completed');
        $limitedJobIds = array_slice($completedJobIds, 0, $limit);

        return $this->getJobsDetails($limitedJobIds);
    }

    public function getFailedJobs(int $limit = 10): array
    {
        $connection = Redis::connection($this->connection);
        $failedJobIds = $connection->smembers(self::PREFIX . 'failed');
        $limitedJobIds = array_slice($failedJobIds, 0, $limit);

        return $this->getJobsDetails($limitedJobIds);
    }

    /**
     * Retorna todos os jobs utilizando Octane::concurrently
     * para buscar os IDs de cada status em paralelo no Redis.
     */
    public function getAllJobs(): array
    {
        $connection = Redis::connection($this->connection);

        [$activeJobIds, $completedJobIds, $failedJobIds] = Octane::concurrently([
            fn () => $connection->smembers(self::PREFIX . 'active'),
            fn () => $connection->smembers(self::PREFIX . 'completed'),
            fn () => $connection->smembers(self::PREFIX . 'failed'),
        ], 3000);

        $allJobIds = array_merge($activeJobIds, $completedJobIds, $failedJobIds);

        return $this->getJobsDetails($allJobIds);
    }

    public function getJobDetails(string $jobId): ?array
    {
        $connection = Redis::connection($this->connection);
        $details = $connection->hgetall(self::PREFIX . $jobId);

        if (empty($details)) {
            return null;
        }

        return $this->formatJobDetails($jobId, $details);
    }

    /**
     * Retorna estatísticas das filas utilizando Octane::concurrently
     * para buscar as contagens de cada status em paralelo no Redis.
     */
    public function getStatistics(): array
    {
        $connection = Redis::connection($this->connection);

        [$activeCount, $completedCount, $failedCount] = Octane::concurrently([
            fn () => $connection->scard(self::PREFIX . 'active'),
            fn () => $connection->scard(self::PREFIX . 'completed'),
            fn () => $connection->scard(self::PREFIX . 'failed'),
        ], 3000);

        return [
            'active_count' => (int) $activeCount,
            'completed_count' => (int) $completedCount,
            'failed_count' => (int) $failedCount,
            'total_monitored' => (int) ($activeCount + $completedCount + $failedCount),
        ];
    }

    /**
     * Limpa jobs concluídos e falhados utilizando Octane::concurrently
     * para buscar ambas as listas em paralelo antes da remoção.
     */
    public function clearCompletedAndFailed(): int
    {
        $connection = Redis::connection($this->connection);

        // Busca ambas as listas em paralelo
        [$completedJobs, $failedJobs] = Octane::concurrently([
            fn () => $connection->smembers(self::PREFIX . 'completed'),
            fn () => $connection->smembers(self::PREFIX . 'failed'),
        ], 3000);

        $deletedCount = 0;

        // Remove os hashes dos jobs
        foreach (array_merge($completedJobs, $failedJobs) as $jobId) {
            $connection->del(self::PREFIX . $jobId);
            $deletedCount++;
        }

        // Limpa as listas
        $connection->del(self::PREFIX . 'completed');
        $connection->del(self::PREFIX . 'failed');

        return $deletedCount;
    }

    /**
     * Retorna detalhes de múltiplos jobs
     *
     * @param array $jobIds
     * @return array
     */
    private function getJobsDetails(array $jobIds): array
    {
        $connection = Redis::connection($this->connection);
        $jobs = [];

        foreach ($jobIds as $jobId) {
            $details = $connection->hgetall(self::PREFIX . $jobId);
            if (!empty($details)) {
                $jobs[] = $this->formatJobDetails($jobId, $details);
            }
        }

        $jobsOrdered = collect($jobs)->sortByDesc('started_at')
                                     ->values()
                                     ->all();

        return $jobsOrdered;
    }

    /**
     * Formata os detalhes do job para retorno
     *
     * @param string $jobId
     * @param array $details
     * @return array
     */
    private function formatJobDetails(string $jobId, array $details): array
    {
        $formatted = [
            'job_id' => $jobId,
            'job_class' => $details['job'] ?? 'Unknown',
            'queue' => $details['queue'] ?? 'default',
            'status' => $details['status'] ?? 'unknown',
            'attempts' => (int) ($details['attempts'] ?? 0),
            'started_at' => $details['started_at'] ?? null,
            'finished_at' => $details['finished_at'] ?? null,
        ];

        // Adiciona informações de erro se existirem
        if (isset($details['error_message'])) {
            $formatted['error'] = [
                'message' => $details['error_message'],
                'trace' => $details['error_trace'] ?? null,
            ];
            $formatted['failed_at'] = $details['failed_at'] ?? null;
        }

        // Calcula duração se o job já terminou
        if (isset($details['started_at']) && isset($details['finished_at'])) {
            $start = Carbon::parse($details['started_at']);
            $end = Carbon::parse($details['finished_at']);
            $formatted['duration_seconds'] = (int) $end->diffInSeconds($start);
        }

        return $formatted;
    }
}
