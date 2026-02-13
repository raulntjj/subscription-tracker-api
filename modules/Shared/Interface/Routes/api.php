<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Shared API Routes
|--------------------------------------------------------------------------
|
| Rotas globais da aplicação (health check, status, etc)
|
*/

Route::get('/status', function () {
    $dbStatus = 'disconnected';
    $cacheStatus = 'disconnected';

    try {
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    try {
        Cache::set('health_check', true, 10);
        $cacheStatus = Cache::get('health_check') ? 'connected' : 'error';
    } catch (\Exception $e) {
        $cacheStatus = 'error: ' . $e->getMessage();
    }

    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'environment' => config('app.env'),
        'timestamp' => now()->toIso8601String(),
        'services' => [
            'database' => $dbStatus,
            'cache' => $cacheStatus,
        ],
    ]);
});
