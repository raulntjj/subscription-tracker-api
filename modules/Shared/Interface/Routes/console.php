<?php

use Illuminate\Support\Facades\Schedule;
use Modules\Subscription\Application\Jobs\CheckBillingJob;

/*
|--------------------------------------------------------------------------
| Console Routes / Task Scheduling
|--------------------------------------------------------------------------
*/

Schedule::job(new CheckBillingJob())
    ->everyMinute()
    // ->dailyAt('00:00')
    // ->timezone('America/Sao_Paulo')
    ->name('check-billing-daily')
    ->description('Verifica assinaturas que vencem hoje e cria histórico de billing');
