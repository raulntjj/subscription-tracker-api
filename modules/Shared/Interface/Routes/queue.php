<?php

use Modules\Shared\Interface\Http\Controllers\QueueMonitorController;

/*
|--------------------------------------------------------------------------
| Queue Monitor Routes
|--------------------------------------------------------------------------
|
| Rotas para monitoramento de filas em tempo real
|
*/

Route::prefix('queue-monitor')->group(function () {
    Route::get('/', [QueueMonitorController::class, 'index'])->name('queue-monitor.index');
    Route::get('/active', [QueueMonitorController::class, 'active'])->name('queue-monitor.active');
    Route::get('/failed', [QueueMonitorController::class, 'failed'])->name('queue-monitor.failed');
    Route::delete('/clear', [QueueMonitorController::class, 'clear'])->name('queue-monitor.clear');
    Route::get('/{jobId}', [QueueMonitorController::class, 'show'])->name('queue-monitor.show');
});
