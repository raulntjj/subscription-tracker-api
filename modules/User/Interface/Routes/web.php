<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Interface\Http\Controllers\UserController;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;

/*
|--------------------------------------------------------------------------
| User Module Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(Authenticate::class)->prefix('users')->group(function () {
    // Listagem paginada com busca e ordenação
    Route::get('/', [UserController::class, 'paginated']);

    // Opções para selects/autocompletes
    Route::get('/options', [UserController::class, 'options']);

    // CRUD
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::patch('/{id}', [UserController::class, 'partialUpdate']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});
