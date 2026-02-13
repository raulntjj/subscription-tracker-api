<?php

declare(strict_types=1);

namespace Modules\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestSchedulerCommand extends Command
{
    protected $signature = 'test:scheduler';

    protected $description = 'Comando de teste para verificar se o scheduler está funcionando';

    public function handle(): int
    {
        $timestamp = now()->toDateTimeString();

        Log::info('⏰ Scheduler Executado!', [
            'timestamp' => $timestamp,
            'command' => $this->signature,
        ]);

        $this->info("✅ Scheduler funcionando! Executado em: {$timestamp}");

        return self::SUCCESS;
    }
}
