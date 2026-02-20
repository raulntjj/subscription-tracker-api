<?php

declare(strict_types=1);

namespace Modules\Shared\Interface\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\Queries\GetAllJobsQuery;
use Modules\Shared\Interface\Responses\ApiResponse;
use Modules\Shared\Application\Queries\GetActiveJobsQuery;
use Modules\Shared\Application\Queries\GetFailedJobsQuery;
use Modules\Shared\Application\Queries\GetJobDetailsQuery;
use Modules\Shared\Application\Queries\GetQueueMetricsQuery;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;
use Modules\Shared\Application\UseCases\ClearQueueMonitorLogsUseCase;

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
    public function index(Request $request): JsonResponse
    {
        try {
            $jobList = $this->getAllJobsQuery->execute();

            return ApiResponse::success(
                data: $jobList->toArray(),
                message: __('Shared::message.jobs_retrieved_successfully'),
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Error fetching all jobs', context: [
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
                message: __('Shared::message.queue_metrics_retrieved_successfully'),
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Error fetching queue metrics', context: [
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
                message: __('Shared::message.active_jobs_retrieved_successfully'),
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Error fetching active jobs', context: [
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
                message: __('Shared::message.failed_jobs_retrieved_successfully'),
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Error fetching failed jobs', context: [
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
            $job = $this->getJobDetailsQuery->execute(jobId: $jobId);

            if ($job === null) {
                return ApiResponse::notFound(message: __('Shared::message.job_not_found'));
            }

            return ApiResponse::success(
                data: $job->toArray(),
                message: __('Shared::message.job_details_retrieved_successfully'),
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Error fetching job details', context: [
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
                message: __('Shared::message.cleanup_completed', ['count' => $result->deletedCount]),
            );
        } catch (Throwable $e) {
            $this->logger()->error(message: 'Error clearing queue monitor logs', context: [
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(exception: $e);
        }
    }
}
