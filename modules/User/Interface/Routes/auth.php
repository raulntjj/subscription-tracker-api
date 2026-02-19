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
| POST   /login    → Realiza login e retorna JWT (público)
| POST   /refresh  → Renova token EXPIRADO dentro do refresh_ttl (aceita token expirado)
| POST   /logout   → Invalida token (autenticado)
| GET    /me       → Retorna usuário autenticado (autenticado)
|
| Importante:
| - /refresh aceita tokens EXPIRADOS dentro da janela de refresh (14 dias por padrão)
| - Tokens expirados NÃO funcionam em outras rotas autenticadas
| - Após o refresh_ttl, o usuário precisa fazer login novamente
|
*/

// Rota pública
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

// Rotas autenticadas
Route::middleware(Authenticate::class)->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
