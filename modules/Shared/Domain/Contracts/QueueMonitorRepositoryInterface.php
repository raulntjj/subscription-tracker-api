<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Contracts;

interface QueueMonitorRepositoryInterface
{
    /**
     * Retorna todos os jobs ativos
     *
     * @return array
     */
    public function getActiveJobs(): array;

    /**
     * Retorna todos os jobs concluídos
     *
     * @param int $limit
     * @return array
     */
    public function getCompletedJobs(int $limit = 10): array;

    /**
     * Retorna todos os jobs falhados
     *
     * @param int $limit
     * @return array
     */
    public function getFailedJobs(int $limit = 10): array;

    /**
     * Retorna todos os jobs (ativos, concluídos e falhados)
     *
     * @return array
     */
    public function getAllJobs(): array;

    /**
     * Retorna detalhes de um job específico
     *
     * @param string $jobId
     * @return array|null
     */
    public function getJobDetails(string $jobId): ?array;

    /**
     * Retorna estatísticas gerais das filas
     *
     * @return array{active_count: int, completed_count: int, failed_count: int, total_monitored: int}
     */
    public function getStatistics(): array;

    /**
     * Remove jobs concluídos e falhados do monitoramento
     *
     * @return int Número de jobs removidos
     */
    public function clearCompletedAndFailed(): int;
}
