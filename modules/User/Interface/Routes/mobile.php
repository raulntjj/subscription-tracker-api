<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;
use Modules\User\Interface\Controllers\MobileUserController;

/*
|--------------------------------------------------------------------------
| User Module Mobile Routes
|--------------------------------------------------------------------------
*/

Route::middleware(Authenticate::class)->prefix('users')->group(function () {
    // Listagem com cursor pagination, busca e ordenação
    Route::get('/', [MobileUserController::class, 'index']);
});
