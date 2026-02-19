<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Queries;

use Modules\Shared\Application\DTOs\JobListDTO;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class GetActiveJobsQuery
{
    public function __construct(
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository
    ) {
    }

    /**
     * Retorna apenas os jobs ativos
     *
     * @return JobListDTO
     */
    public function execute(): JobListDTO
    {
        $jobs = $this->queueMonitorRepository->getActiveJobs();

        return JobListDTO::fromArray($jobs);
    }
}
