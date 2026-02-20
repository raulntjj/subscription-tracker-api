<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\UserRepository;
use Modules\Shared\Infrastructure\Concerns\LoadsModuleSeeders;

final class UserServiceProvider extends ServiceProvider
{
    use LoadsModuleSeeders;
    public function register(): void
    {
        $this->app->bind(
            abstract: UserRepositoryInterface::class,
            concrete: UserRepository::class,
        );
    }

    public function boot(): void
    {
        // Rotas Web com prefixo '/api/web/v1'
        Route::prefix('/api/web/v1')
            ->middleware('api')
            ->group(__DIR__ . '/../../Interface/Routes/web.php');

        // Rotas Mobile com prefixo '/api/mobile/v1'
        Route::prefix('/api/mobile/v1')
            ->middleware('api')
            ->group(__DIR__ . '/../../Interface/Routes/mobile.php');

        // Rotas de Autenticação com prefixo '/api/auth/v1'
        Route::prefix('/api/auth/v1')
            ->middleware('api')
            ->group(__DIR__ . '/../../Interface/Routes/auth.php');

        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');
        $this->loadSeedersFrom(__DIR__ . '/../Persistence/Seeders');
    }
}
