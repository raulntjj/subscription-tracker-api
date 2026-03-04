<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Queries;

use Modules\Shared\Application\DTOs\JobListDTO;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class GetAllJobsQuery
{
    public function __construct(
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository,
    ) {
    }

    /**
     * Retorna todos os jobs (ativos, concluídos e falhados)
     *
     * Utiliza Octane::concurrently para buscar os 3 tipos de jobs
     * em paralelo e depois os consolida em uma única lista.
     *
     * @return JobListDTO
     */
    public function execute(): JobListDTO
    {
        $jobs = $this->queueMonitorRepository->getAllJobs();
        return JobListDTO::fromArray($jobs);
    }
}
