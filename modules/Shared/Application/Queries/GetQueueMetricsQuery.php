<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Queries;

use Laravel\Octane\Facades\Octane;
use Modules\Shared\Application\DTOs\QueueMetricsDTO;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class GetQueueMetricsQuery
{
    public function __construct(
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository,
    ) {
    }

    /**
     * Retorna métricas das filas com detalhes de jobs por status
     */
    public function execute(): QueueMetricsDTO
    {
        [$stats, $activeJobs, $recentCompleted, $recentFailed] = Octane::concurrently([
            fn () => $this->queueMonitorRepository->getStatistics(),
            fn () => $this->queueMonitorRepository->getActiveJobs(),
            fn () => $this->queueMonitorRepository->getCompletedJobs(10),
            fn () => $this->queueMonitorRepository->getFailedJobs(10),
        ], 5000);

        return QueueMetricsDTO::fromArray([
            'statistics' => $stats,
            'active_jobs' => $activeJobs,
            'recent_completed' => $recentCompleted,
            'recent_failed' => $recentFailed,
        ]);
    }
}
