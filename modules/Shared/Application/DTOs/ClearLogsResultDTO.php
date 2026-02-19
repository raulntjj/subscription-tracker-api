<?php

declare(strict_types=1);

namespace Modules\Shared\Application\DTOs;

final readonly class ClearLogsResultDTO
{
    public function __construct(
        public int $deletedCount,
    ) {
    }

    /**
     * Converte o DTO para array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'deleted_count' => $this->deletedCount,
        ];
    }
}
