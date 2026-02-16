<?php

declare(strict_types=1);

namespace Modules\Shared\Interface\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;

final class QueueMonitorController extends Controller
{
    /**
     * Retorna o status geral das filas
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $connection = Redis::connection('sessions');
        
        // Coleta estatísticas
        $activeJobs = $connection->smembers('queue_monitor:active');
        $completedJobs = $connection->smembers('queue_monitor:completed');
        $failedJobs = $connection->smembers('queue_monitor:failed');
        
        $stats = [
            'active_count' => count($activeJobs),
            'completed_count' => count($completedJobs),
            'failed_count' => count($failedJobs),
            'total_monitored' => count($activeJobs) + count($completedJobs) + count($failedJobs),
        ];

        // Coleta detalhes dos jobs ativos
        $activeJobsDetails = [];
        foreach ($activeJobs as $jobId) {
            $details = $connection->hgetall("queue_monitor:$jobId");
            if (!empty($details)) {
                $activeJobsDetails[] = $this->formatJobDetails($jobId, $details);
            }
        }

        // Coleta os últimos 10 jobs concluídos
        $completedJobsDetails = [];
        $completedJobsSlice = array_slice($completedJobs, 0, 10);
        foreach ($completedJobsSlice as $jobId) {
            $details = $connection->hgetall("queue_monitor:$jobId");
            if (!empty($details)) {
                $completedJobsDetails[] = $this->formatJobDetails($jobId, $details);
            }
        }

        // Coleta os últimos 10 jobs falhados
        $failedJobsDetails = [];
        $failedJobsSlice = array_slice($failedJobs, 0, 10);
        foreach ($failedJobsSlice as $jobId) {
            $details = $connection->hgetall("queue_monitor:$jobId");
            if (!empty($details)) {
                $failedJobsDetails[] = $this->formatJobDetails($jobId, $details);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $stats,
                'active_jobs' => $activeJobsDetails,
                'recent_completed' => $completedJobsDetails,
                'recent_failed' => $failedJobsDetails,
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Retorna detalhes de um job específico
     * 
     * @param string $jobId
     * @return JsonResponse
     */
    public function show(string $jobId): JsonResponse
    {
        $connection = Redis::connection('sessions');
        $details = $connection->hgetall("queue_monitor:$jobId");
        
        if (empty($details)) {
            return response()->json([
                'success' => false,
                'message' => 'Job não encontrado ou expirado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatJobDetails($jobId, $details),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Retorna apenas os jobs ativos
     * 
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        $connection = Redis::connection('sessions');
        $activeJobs = $connection->smembers('queue_monitor:active');
        
        $jobs = [];
        foreach ($activeJobs as $jobId) {
            $details = $connection->hgetall("queue_monitor:$jobId");
            if (!empty($details)) {
                $jobs[] = $this->formatJobDetails($jobId, $details);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'count' => count($jobs),
                'jobs' => $jobs,
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Retorna apenas os jobs falhados
     * 
     * @return JsonResponse
     */
    public function failed(): JsonResponse
    {
        $connection = Redis::connection('sessions');
        $failedJobs = $connection->smembers('queue_monitor:failed');
        
        $jobs = [];
        foreach ($failedJobs as $jobId) {
            $details = $connection->hgetall("queue_monitor:$jobId");
            if (!empty($details)) {
                $jobs[] = $this->formatJobDetails($jobId, $details);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'count' => count($jobs),
                'jobs' => $jobs,
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Limpa os logs de jobs concluídos e falhados
     * 
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        $connection = Redis::connection('sessions');
        
        // Pega todos os jobs concluídos e falhados
        $completedJobs = $connection->smembers('queue_monitor:completed');
        $failedJobs = $connection->smembers('queue_monitor:failed');
        
        $deletedCount = 0;
        
        // Remove os hashes dos jobs
        foreach (array_merge($completedJobs, $failedJobs) as $jobId) {
            $connection->del("queue_monitor:$jobId");
            $deletedCount++;
        }
        
        // Limpa as listas
        $connection->del('queue_monitor:completed');
        $connection->del('queue_monitor:failed');
        
        return response()->json([
            'success' => true,
            'message' => "Limpeza concluída. {$deletedCount} jobs removidos.",
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Formata os detalhes do job para exibição
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
            $start = \Carbon\Carbon::parse($details['started_at']);
            $end = \Carbon\Carbon::parse($details['finished_at']);
            $formatted['duration_seconds'] = $end->diffInSeconds($start);
        }

        return $formatted;
    }
}
