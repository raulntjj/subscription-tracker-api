<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

final readonly class QueueMetricsDTO
{
    /**
     * @param JobDTO[] $activeJobs
     * @param JobDTO[] $recentCompleted
     * @param JobDTO[] $recentFailed
     */
    public function __construct(
        public QueueStatisticsDTO $statistics,
        public array $activeJobs,
        public array $recentCompleted,
        public array $recentFailed,
    ) {
    }

    /**
     * Cria um QueueMetricsDTO a partir de arrays
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            statistics: QueueStatisticsDTO::fromArray($data['statistics']),
            activeJobs: array_map(
                fn (array $jobData) => JobDTO::fromArray($jobData),
                $data['active_jobs'],
            ),
            recentCompleted: array_map(
                fn (array $jobData) => JobDTO::fromArray($jobData),
                $data['recent_completed'],
            ),
            recentFailed: array_map(
                fn (array $jobData) => JobDTO::fromArray($jobData),
                $data['recent_failed'],
            ),
        );
    }

    /**
     * Converte o DTO para array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'statistics' => $this->statistics->toArray(),
            'active_jobs' => array_map(
                fn (JobDTO $job) => $job->toArray(),
                $this->activeJobs,
            ),
            'recent_completed' => array_map(
                fn (JobDTO $job) => $job->toArray(),
                $this->recentCompleted,
            ),
            'recent_failed' => array_map(
                fn (JobDTO $job) => $job->toArray(),
                $this->recentFailed,
            ),
        ];
    }
}
