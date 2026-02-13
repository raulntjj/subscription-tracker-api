<?php

use Illuminate\Support\Facades\Schedule;
use Modules\Subscription\Application\Jobs\CheckBillingJob;

/*
|--------------------------------------------------------------------------
| Console Routes / Task Scheduling
|--------------------------------------------------------------------------
*/

// Teste do Scheduler - executa a cada minuto
Schedule::command('test:scheduler')->everyMinute();

Schedule::job(new CheckBillingJob())
    ->dailyAt('00:00')
    ->timezone('America/Sao_Paulo')
    ->name('check-billing-daily')
    ->description('Verifica assinaturas que vencem hoje e cria histórico de billing');
