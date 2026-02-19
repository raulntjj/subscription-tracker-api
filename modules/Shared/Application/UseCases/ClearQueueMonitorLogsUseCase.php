<?php

declare(strict_types=1);

namespace Modules\Shared\Application\UseCases;

use Modules\Shared\Application\DTOs\ClearLogsResultDTO;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final class ClearQueueMonitorLogsUseCase
{
    use Loggable;

    public function __construct(
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository
    ) {
    }

    /**
     * Limpa os logs de jobs concluídos e falhados
     * 
     * @return ClearLogsResultDTO
     */
    public function execute(): ClearLogsResultDTO
    {
        $deletedCount = $this->queueMonitorRepository->clearCompletedAndFailed();

        $this->logger()->info('Queue monitor logs cleared', [
            'deleted_count' => $deletedCount,
        ]);

        return new ClearLogsResultDTO(deletedCount: $deletedCount);
    }
}
