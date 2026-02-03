<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

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
