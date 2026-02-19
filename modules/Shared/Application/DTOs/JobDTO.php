<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

final readonly class JobDTO
{
    public function __construct(
        public string $jobId,
        public string $jobClass,
        public string $queue,
        public string $status,
        public int $attempts,
        public ?string $startedAt,
        public ?string $finishedAt,
        public ?string $failedAt = null,
        public ?JobErrorDTO $error = null,
        public ?int $durationSeconds = null,
    ) {
    }

    /**
     * Cria um JobDTO a partir de um array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $error = null;
        if (isset($data['error'])) {
            $error = JobErrorDTO::fromArray($data['error']);
        }

        return new self(
            jobId: $data['job_id'],
            jobClass: $data['job_class'],
            queue: $data['queue'],
            status: $data['status'],
            attempts: $data['attempts'],
            startedAt: $data['started_at'] ?? null,
            finishedAt: $data['finished_at'] ?? null,
            failedAt: $data['failed_at'] ?? null,
            error: $error,
            durationSeconds: $data['duration_seconds'] ?? null,
        );
    }

    /**
     * Converte o DTO para array
     * 
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'job_id' => $this->jobId,
            'job_class' => $this->jobClass,
            'queue' => $this->queue,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
        ];

        if ($this->failedAt !== null) {
            $data['failed_at'] = $this->failedAt;
        }

        if ($this->error !== null) {
            $data['error'] = $this->error->toArray();
        }

        if ($this->durationSeconds !== null) {
            $data['duration_seconds'] = $this->durationSeconds;
        }

        return $data;
    }
}
