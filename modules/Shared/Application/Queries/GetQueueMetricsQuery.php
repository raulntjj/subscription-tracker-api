<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Queries;

use Modules\Shared\Application\DTOs\QueueMetricsDTO;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class GetQueueMetricsQuery
{
    public function __construct(
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository
    ) {
    }

    /**
     * Retorna métricas das filas com detalhes de jobs por status
     *
     * @return QueueMetricsDTO
     */
    public function execute(): QueueMetricsDTO
    {
        $stats = $this->queueMonitorRepository->getStatistics();
        $activeJobs = $this->queueMonitorRepository->getActiveJobs();
        $recentCompleted = $this->queueMonitorRepository->getCompletedJobs(10);
        $recentFailed = $this->queueMonitorRepository->getFailedJobs(10);

        return QueueMetricsDTO::fromArray([
            'statistics' => $stats,
            'active_jobs' => $activeJobs,
            'recent_completed' => $recentCompleted,
            'recent_failed' => $recentFailed,
        ]);
    }
}
