<?php

declare(strict_types=1);

namespace Modules\Subscription\Console\Commands;

use Illuminate\Console\Command;
use Modules\Subscription\Application\Jobs\CheckBillingJob;

/**
 * Comando para verificar e processar assinaturas que vencem hoje
 * 
 * Este comando deve ser agendado no Kernel para rodar diariamente
 * Exemplo: $schedule->command('subscription:check-billing')->daily();
 */
final class CheckBillingCommand extends Command
{
    /**
     * Nome e assinatura do comando
     *
     * @var string
     */
    protected $signature = 'subscription:check-billing
                            {--sync : Run synchronously instead of dispatching to queue}';

    /**
     * Descrição do comando
     *
     * @var string
     */
    protected $description = 'Check and process subscriptions due for billing today';

    /**
     * Executa o comando
     */
    public function handle(): int
    {
        $this->info('🔍 Checking subscriptions due for billing...');

        try {
            if ($this->option('sync')) {
                // Executa sincronamente
                $this->info('⚡ Running synchronously...');
                CheckBillingJob::dispatchSync();
            } else {
                // Despacha para a fila
                $this->info('📤 Dispatching to queue...');
                CheckBillingJob::dispatch();
            }

            $this->info('✅ Billing check job dispatched successfully!');
            
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Failed to dispatch billing check job:');
            $this->error($e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
