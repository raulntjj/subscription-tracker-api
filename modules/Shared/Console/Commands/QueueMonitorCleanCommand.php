<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Modules\Shared\Application\UseCases\ClearQueueMonitorLogsUseCase;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;

final class QueueMonitorCleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue-monitor:clean 
                            {--force : Força a limpeza sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa logs de jobs concluídos e falhados do monitoramento de filas';

    public function __construct(
        private readonly ClearQueueMonitorLogsUseCase $clearQueueMonitorLogsUseCase,
        private readonly QueueMonitorRepositoryInterface $queueMonitorRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');

        // Busca estatísticas antes da limpeza
        $stats = $this->queueMonitorRepository->getStatistics();
        $totalToClean = $stats['completed_count'] + $stats['failed_count'];

        if ($totalToClean === 0) {
            $this->info('✓ Nenhum job para limpar.');
            return self::SUCCESS;
        }

        // Mostra informações
        $this->info("Encontrados:");
        $this->line("  - {$stats['completed_count']} jobs concluídos");
        $this->line("  - {$stats['failed_count']} jobs falhados");
        $this->line("  Total: {$totalToClean} jobs serão removidos");
        $this->newLine();

        // Confirma a ação
        if (!$force && !$this->confirm('Deseja continuar?', true)) {
            $this->warn('Operação cancelada.');
            return self::SUCCESS;
        }

        // Executa a limpeza
        $this->info('Limpando jobs...');

        $result = $this->clearQueueMonitorLogsUseCase->execute();

        $this->newLine();
        $this->info("✓ {$result->deletedCount} jobs foram removidos com sucesso!");

        return self::SUCCESS;
    }
}
