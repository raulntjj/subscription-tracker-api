<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Subscription\Domain\Events\SubscriptionRenewed;
use Modules\Shared\Infrastructure\Concerns\LoadsModuleSeeders;
use Modules\Subscription\Console\Commands\CheckBillingCommand;
use Modules\Subscription\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\Subscription\Domain\Contracts\WebhookConfigRepositoryInterface;
use Modules\Subscription\Infrastructure\Persistence\SubscriptionRepository;
use Modules\Subscription\Domain\Contracts\BillingHistoryRepositoryInterface;
use Modules\Subscription\Infrastructure\Persistence\WebhookConfigRepository;
use Modules\Subscription\Infrastructure\Persistence\BillingHistoryRepository;
use Modules\Subscription\Application\Listeners\DispatchWebhookOnSubscriptionRenewed;

final class SubscriptionServiceProvider extends ServiceProvider
{
    use LoadsModuleSeeders;
    public function register(): void
    {
        $this->app->bind(
            SubscriptionRepositoryInterface::class,
            SubscriptionRepository::class,
        );

        $this->app->bind(
            BillingHistoryRepositoryInterface::class,
            BillingHistoryRepository::class,
        );

        $this->app->bind(
            WebhookConfigRepositoryInterface::class,
            WebhookConfigRepository::class,
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

        $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');
        $this->loadSeedersFrom(__DIR__ . '/../Persistence/Seeders');
        $this->loadTranslationsFrom(__DIR__ . '/../../Infrastructure/Lang', 'Subscription');

        // Registra event listeners
        Event::listen(
            SubscriptionRenewed::class,
            DispatchWebhookOnSubscriptionRenewed::class,
        );

        // Registra comandos do console
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckBillingCommand::class,
            ]);
        }
    }
}
