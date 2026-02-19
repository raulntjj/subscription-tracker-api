<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

final readonly class QueueStatisticsDTO
{
    public function __construct(
        public int $activeCount,
        public int $completedCount,
        public int $failedCount,
        public int $totalMonitored,
    ) {
    }

    /**
     * Cria um QueueStatisticsDTO a partir de um array
     * 
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            activeCount: $data['active_count'],
            completedCount: $data['completed_count'],
            failedCount: $data['failed_count'],
            totalMonitored: $data['total_monitored'],
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
            'active_count' => $this->activeCount,
            'completed_count' => $this->completedCount,
            'failed_count' => $this->failedCount,
            'total_monitored' => $this->totalMonitored,
        ];
    }
}
