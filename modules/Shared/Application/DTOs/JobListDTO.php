<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

final readonly class JobListDTO
{
    /**
     * @param JobDTO[] $jobs
     */
    public function __construct(
        public int $total,
        public array $jobs,
    ) {
    }

    /**
     * Cria um JobListDTO a partir de um array de jobs
     * 
     * @param array $jobsData
     * @return self
     */
    public static function fromArray(array $jobsData): self
    {
        $jobs = array_map(
            fn(array $jobData) => JobDTO::fromArray($jobData),
            $jobsData
        );

        return new self(
            total: count($jobs),
            jobs: $jobs,
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
            'total' => $this->total,
            'jobs' => array_map(
                fn(JobDTO $job) => $job->toArray(),
                $this->jobs
            ),
        ];
    }
}
