<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Queries;

use Modules\Shared\Application\DTOs\JobDTO;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class GetJobDetailsQuery
{
    public function __construct(
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository,
    ) {
    }

    /**
     * Retorna detalhes de um job específico
     *
     * @param string $jobId
     * @return JobDTO|null
     */
    public function execute(string $jobId): ?JobDTO
    {
        $jobData = $this->queueMonitorRepository->getJobDetails($jobId);

        if ($jobData === null) {
            return null;
        }

        return JobDTO::fromArray($jobData);
    }
}
