<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Interface\Http\Controllers\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Subscription Module Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('subscriptions')->group(function () {
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
    Route::patch('/{id}', [SubscriptionController::class, 'partialUpdate']);
    Route::delete('/{id}', [SubscriptionController::class, 'destroy']);
});
