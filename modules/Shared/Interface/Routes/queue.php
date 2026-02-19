<?php

use Modules\Shared\Interface\Http\Controllers\QueueMonitorController;

/*
|--------------------------------------------------------------------------
| Queue Monitor Routes
|--------------------------------------------------------------------------
|
| Rotas para monitoramento de filas em tempo real
|
| GET    /             → Lista todos os jobs (ativos, concluídos e falhados)
| GET    /metrics      → Estatísticas e métricas gerais das filas
| GET    /active       → Lista apenas jobs ativos
| GET    /failed       → Lista apenas jobs falhados
| GET    /{jobId}      → Detalhes de um job específico
| DELETE /clear        → Limpa logs de jobs concluídos e falhados
|
*/

Route::prefix('queue-monitor')->group(function () {
    Route::get('/', [QueueMonitorController::class, 'index'])->name('queue-monitor.index');
    Route::get('/metrics', [QueueMonitorController::class, 'metrics'])->name('queue-monitor.metrics');
    Route::get('/active', [QueueMonitorController::class, 'active'])->name('queue-monitor.active');
    Route::get('/failed', [QueueMonitorController::class, 'failed'])->name('queue-monitor.failed');
    Route::delete('/clear', [QueueMonitorController::class, 'clear'])->name('queue-monitor.clear');
    Route::get('/{jobId}', [QueueMonitorController::class, 'show'])->name('queue-monitor.show');
});

