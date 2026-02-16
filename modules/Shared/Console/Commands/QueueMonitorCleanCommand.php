<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

final class QueueMonitorCleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue-monitor:clean 
                            {--force : Força a limpeza sem confirmação}
                            {--failed-only : Limpa apenas jobs falhados}
                            {--completed-only : Limpa apenas jobs concluídos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa logs antigos de monitoramento de filas do Redis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = Redis::connection('sessions');
        
        $failedOnly = $this->option('failed-only');
        $completedOnly = $this->option('completed-only');
        $force = $this->option('force');

        // Determina quais jobs limpar
        $jobsToClean = [];
        
        if ($failedOnly) {
            $jobsToClean = $connection->smembers('queue_monitor:failed');
            $type = 'falhados';
        } elseif ($completedOnly) {
            $jobsToClean = $connection->smembers('queue_monitor:completed');
            $type = 'concluídos';
        } else {
            $completedJobs = $connection->smembers('queue_monitor:completed');
            $failedJobs = $connection->smembers('queue_monitor:failed');
            $jobsToClean = array_merge($completedJobs, $failedJobs);
            $type = 'concluídos e falhados';
        }

        $count = count($jobsToClean);

        if ($count === 0) {
            $this->info('✓ Nenhum job para limpar.');
            return self::SUCCESS;
        }

        // Mostra informações
        $this->info("Encontrados {$count} jobs {$type} para limpar.");

        // Confirma a ação
        if (!$force && !$this->confirm('Deseja continuar?', true)) {
            $this->warn('Operação cancelada.');
            return self::SUCCESS;
        }

        // Executa a limpeza
        $this->info('Limpando jobs...');
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $deletedCount = 0;
        foreach ($jobsToClean as $jobId) {
            $connection->del("queue_monitor:$jobId");
            $deletedCount++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Limpa as listas correspondentes
        if ($failedOnly) {
            $connection->del('queue_monitor:failed');
        } elseif ($completedOnly) {
            $connection->del('queue_monitor:completed');
        } else {
            $connection->del('queue_monitor:completed');
            $connection->del('queue_monitor:failed');
        }

        $this->info("✓ {$deletedCount} jobs foram removidos com sucesso!");

        return self::SUCCESS;
    }
}
