<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;
use Modules\Subscription\Interface\Controllers\MobileSubscriptionController;

/*
|--------------------------------------------------------------------------
| Subscription Module Mobile Routes
|--------------------------------------------------------------------------
*/

Route::middleware(Authenticate::class)->prefix('subscriptions')->group(function () {
    // Listagem com cursor pagination, busca e ordenação
    Route::get('/', [MobileSubscriptionController::class, 'index']);
});
