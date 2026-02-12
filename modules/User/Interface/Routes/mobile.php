<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Interface\Http\Controllers\MobileUserController;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;

/*
|--------------------------------------------------------------------------
| User Module Mobile Routes
|--------------------------------------------------------------------------
*/

Route::middleware(Authenticate::class)->prefix('users')->group(function () {
    // Listagem com cursor pagination, busca e ordenação
    Route::get('/', [MobileUserController::class, 'index']);
});