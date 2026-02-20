<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;
use Modules\Subscription\Interface\Controllers\SubscriptionController;
use Modules\Subscription\Interface\Controllers\WebhookConfigController;

/*
|--------------------------------------------------------------------------
| Subscription Module Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(Authenticate::class)->prefix('subscriptions')->group(function () {
    // Listagem paginada com busca e ordenação
    Route::get('/', [SubscriptionController::class, 'paginated']);

    // Opções para selects/autocompletes
    Route::get('/options', [SubscriptionController::class, 'options']);

    // Orçamento mensal (antes do {id} para evitar conflito de rotas)
    Route::get('/budget', [SubscriptionController::class, 'budget']);

    // CRUD
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::get('/{id}', [SubscriptionController::class, 'show']);
    Route::put('/{id}', [SubscriptionController::class, 'update']);
    Route::delete('/{id}', [SubscriptionController::class, 'destroy']);
});

Route::middleware(Authenticate::class)->prefix('webhooks')->group(function () {
    // Listagem
    Route::get('/', [WebhookConfigController::class, 'index']);

    // CRUD
    Route::post('/', [WebhookConfigController::class, 'store']);
    Route::get('/{id}', [WebhookConfigController::class, 'show']);
    Route::put('/{id}', [WebhookConfigController::class, 'update']);
    Route::delete('/{id}', [WebhookConfigController::class, 'destroy']);

    // Ações especiais
    Route::patch('/{id}/activate', [WebhookConfigController::class, 'activate']);
    Route::patch('/{id}/deactivate', [WebhookConfigController::class, 'deactivate']);
    Route::post('/{id}/test', [WebhookConfigController::class, 'test']);
});
