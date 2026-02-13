<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Application\Jobs\TestRabbitMQJob;

/*
|--------------------------------------------------------------------------
| Shared Web Routes
|--------------------------------------------------------------------------
|
| Rotas web globais da aplicação
|
*/

Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'version' => '1.0.0',
        'status' => 'running',
    ]);
});

// Rota de teste para RabbitMQ
Route::post('/test/rabbitmq', function () {
    $message = request('message', 'Teste de Job no RabbitMQ');
    
    TestRabbitMQJob::dispatch($message);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Job despachado para o RabbitMQ',
        'data' => [
            'message' => $message,
            'timestamp' => now()->toDateTimeString(),
        ],
    ]);
});
