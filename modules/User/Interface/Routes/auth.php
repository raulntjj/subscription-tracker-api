<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Interface\Http\Controllers\AuthController;
use Modules\Shared\Infrastructure\Auth\Middleware\Authenticate;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public + Authenticated)
|--------------------------------------------------------------------------
|
| POST   /login    → Realiza login e retorna JWT
| POST   /logout   → Invalida token (autenticado)
| POST   /refresh  → Renova token (autenticado)
| GET    /me        → Retorna usuário autenticado (autenticado)
|
*/

// Rota pública
Route::post('/login', [AuthController::class, 'login']);

// Rotas autenticadas
Route::middleware(Authenticate::class)->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
});
