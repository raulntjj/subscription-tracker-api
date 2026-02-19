<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Queries;

use Modules\Shared\Application\DTOs\JobListDTO;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class GetFailedJobsQuery
{
    public function __construct(
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository
    ) {
    }

    /**
     * Retorna apenas os jobs falhados
     * 
     * @return JobListDTO
     */
    public function execute(): JobListDTO
    {
        $jobs = $this->queueMonitorRepository->getFailedJobs();
        
        return JobListDTO::fromArray($jobs);
    }
}
