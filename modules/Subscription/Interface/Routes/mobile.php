<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Interface\Http\Controllers\MobileSubscriptionController;

/*
|--------------------------------------------------------------------------
| Subscription Module Mobile Routes
|--------------------------------------------------------------------------
*/

Route::prefix('subscriptions')->group(function () {
    // Listagem com cursor pagination, busca e ordenação
    Route::get('/', [MobileSubscriptionController::class, 'index']);
});
