<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Console\Commands\ModuleCreateCommand;
use Modules\Shared\Console\Commands\ModuleMakeMigrationCommand;
use Modules\Shared\Console\Commands\ModuleMakeSeederCommand;
use Modules\Shared\Console\Commands\ModuleSeedCommand;
use Modules\Shared\Console\Commands\ModuleTestCommand;
use Modules\Shared\Console\Commands\QueueMonitorCleanCommand;
use Modules\Shared\Domain\Contracts\CacheServiceInterface;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;
use Modules\Shared\Domain\Contracts\LoggerInterface;
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;
use Modules\Shared\Infrastructure\Auth\JwtService;
use Modules\Shared\Infrastructure\Cache\CacheServiceFactory;
use Modules\Shared\Infrastructure\Logging\LoggerFactory;
use Modules\Shared\Infrastructure\Queue\RedisQueueMonitorRepository;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registra o Logger como singleton
        $this->app->singleton(LoggerInterface::class, function () {
            return LoggerFactory::forModule('Shared');
        });

        // Registra o CacheService como singleton
        $this->app->singleton(CacheServiceInterface::class, function () {
            return CacheServiceFactory::create();
        });

        // Registra o JwtService como singleton
        $this->app->singleton(JwtServiceInterface::class, JwtService::class);

        // Registra o QueueMonitorRepository como singleton
        $this->app->singleton(QueueMonitorRepositoryInterface::class, RedisQueueMonitorRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Interface/Routes/health.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Interface/Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../Interface/Routes/queue.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleSeedCommand::class,
                ModuleTestCommand::class,
                ModuleCreateCommand::class,
                ModuleMakeSeederCommand::class,
                ModuleMakeMigrationCommand::class,
                QueueMonitorCleanCommand::class,
            ]);
        }
    }
}
