<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;
use Modules\Shared\Interface\Http\Controllers\QueueMonitorController;

/*
|--------------------------------------------------------------------------
| Queue Monitor Routes
|--------------------------------------------------------------------------
*/

Route::middleware(Authenticate::class)->prefix('/monitor')
    ->group(function () {
        Route::get('/', [QueueMonitorController::class, 'index'])->name('queue-monitor.index');
        Route::get('/metrics', [QueueMonitorController::class, 'metrics'])->name('queue-monitor.metrics');
        Route::get('/active', [QueueMonitorController::class, 'active'])->name('queue-monitor.active');
        Route::get('/failed', [QueueMonitorController::class, 'failed'])->name('queue-monitor.failed');
        Route::delete('/clear', [QueueMonitorController::class, 'clear'])->name('queue-monitor.clear');
        Route::get('/{jobId}', [QueueMonitorController::class, 'show'])->name('queue-monitor.show');
    });
