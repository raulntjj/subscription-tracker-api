<?php

declare(strict_types=1);

namespace Modules\Shared\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Queries\GetAllJobsQuery;
use Modules\Shared\Application\Queries\GetQueueMetricsQuery;
use Modules\Shared\Application\Queries\GetActiveJobsQuery;
use Modules\Shared\Application\Queries\GetFailedJobsQuery;
use Modules\Shared\Application\Queries\GetJobDetailsQuery;
use Modules\Shared\Application\UseCases\ClearQueueMonitorLogsUseCase;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final class QueueMonitorController extends Controller
{
    use Loggable;

    public function __construct(
        private readonly GetAllJobsQuery $getAllJobsQuery,
        private readonly GetQueueMetricsQuery $getQueueMetricsQuery,
        private readonly GetActiveJobsQuery $getActiveJobsQuery,
        private readonly GetFailedJobsQuery $getFailedJobsQuery,
        private readonly GetJobDetailsQuery $getJobDetailsQuery,
        private readonly ClearQueueMonitorLogsUseCase $clearQueueMonitorLogsUseCase,
    ) {
    }

    /**
     * GET /queue-monitor
     *
     * Retorna a listagem de todos os jobs (ativos, concluídos e falhados)
     */
    public function index(): JsonResponse
    {
        try {
            $jobList = $this->getAllJobsQuery->execute();

            return ApiResponse::success(
                data: $jobList->toArray(),
                message: 'Jobs recuperados com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Error fetching all jobs', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /queue-monitor/metrics
     *
     * Retorna métricas e estatísticas das filas
     */
    public function metrics(): JsonResponse
    {
        try {
            $metrics = $this->getQueueMetricsQuery->execute();

            return ApiResponse::success(
                data: $metrics->toArray(),
                message: 'Métricas recuperadas com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Error fetching queue metrics', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /queue-monitor/active
     *
     * Retorna apenas os jobs ativos
     */
    public function active(): JsonResponse
    {
        try {
            $jobList = $this->getActiveJobsQuery->execute();

            return ApiResponse::success(
                data: $jobList->toArray(),
                message: 'Jobs ativos recuperados com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Error fetching active jobs', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /queue-monitor/failed
     *
     * Retorna apenas os jobs falhados
     */
    public function failed(): JsonResponse
    {
        try {
            $jobList = $this->getFailedJobsQuery->execute();

            return ApiResponse::success(
                data: $jobList->toArray(),
                message: 'Jobs falhados recuperados com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Error fetching failed jobs', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /queue-monitor/{jobId}
     *
     * Retorna detalhes de um job específico
     */
    public function show(string $jobId): JsonResponse
    {
        try {
            $job = $this->getJobDetailsQuery->execute($jobId);

            if ($job === null) {
                return ApiResponse::notFound('Job não encontrado ou expirado.');
            }

            return ApiResponse::success(
                data: $job->toArray(),
                message: 'Detalhes do job recuperados com sucesso.'
            );
        } catch (Throwable $e) {
            $this->logger()->error('Error fetching job details', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * DELETE /queue-monitor/clear
     *
     * Limpa os logs de jobs concluídos e falhados
     */
    public function clear(): JsonResponse
    {
        try {
            $result = $this->clearQueueMonitorLogsUseCase->execute();

            return ApiResponse::success(
                data: $result->toArray(),
                message: "Limpeza concluída. {$result->deletedCount} jobs removidos."
            );
        } catch (Throwable $e) {
            $this->logger()->error('Error clearing queue monitor logs', [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }
}
