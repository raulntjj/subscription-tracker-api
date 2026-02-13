<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Subscription\Console\Commands\CheckBillingCommand;
use Modules\Subscription\Domain\Contracts\BillingHistoryRepositoryInterface;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Infrastructure\Persistence\BillingHistoryRepository;
use Modules\Subscription\Infrastructure\Persistence\SubscriptionRepository;

final class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SubscriptionRepositoryInterface::class,
            SubscriptionRepository::class
        );

        $this->app->bind(
            BillingHistoryRepositoryInterface::class,
            BillingHistoryRepository::class
        );
    }

    public function boot(): void
    {
        // Rotas Web com prefixo '/api/web/v1'
        Route::prefix('/api/web/v1')
            ->group(__DIR__ . '/../../Interface/Routes/web.php');

        // Rotas Mobile com prefixo '/api/mobile/v1'
        Route::prefix('/api/mobile/v1')
            ->group(__DIR__ . '/../../Interface/Routes/mobile.php');

        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');

        // Registra comandos do console
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckBillingCommand::class,
            ]);
        }
    }
}
