<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Tests\Concerns;

/**
 * Adaptive database trait that uses RefreshDatabase in CI and DatabaseTransactions locally.
 *
 * In CI environment (detected via CI env var), it uses RefreshDatabase to ensure
 * migrations run on the fresh in-memory SQLite database.
 *
 * Locally, it uses DatabaseTransactions for faster test execution since the
 * database is already migrated.
 */
trait AdaptiveDatabase
{
    /**
     * Track if migrations have already been run.
     */
    protected static bool $adaptiveDatabaseMigrated = false;

    /**
     * Define which connections should have their transactions rolled back.
     *
     * @return array<int, string>
     */
    protected function connectionsToTransact(): array
    {
        return property_exists($this, 'connectionsToTransact')
            ? $this->connectionsToTransact
            : [null];
    }

    /**
     * Determine if we're running in CI environment.
     */
    protected function runningInCi(): bool
    {
        return env('CI', false) === true
            || env('CI', false) === 'true'
            || env('GITHUB_ACTIONS', false) === 'true';
    }

    /**
     * Boot the testing helper traits.
     * Called automatically by Laravel's TestCase.
     *
     * @return void
     */
    protected function setUpTraits(): array
    {
        $uses = parent::setUpTraits();

        if ($this->runningInCi()) {
            $this->refreshDatabase();
        } else {
            $this->beginDatabaseTransaction();
        }

        return $uses;
    }

    /**
     * Refresh the in-memory database (for CI).
     */
    protected function refreshDatabase(): void
    {
        $this->beforeRefreshingDatabase();

        $this->usingInMemoryDatabase()
            ? $this->refreshInMemoryDatabase()
            : $this->refreshTestDatabase();

        $this->afterRefreshingDatabase();
    }

    /**
     * Determine if an in-memory database is being used.
     */
    protected function usingInMemoryDatabase(): bool
    {
        $database = config("database.connections.{$this->getRefreshConnection()}.database");

        return $database === ':memory:';
    }

    /**
     * Get the database connection that should be used by RefreshDatabase.
     */
    protected function getRefreshConnection(): ?string
    {
        return null;
    }

    /**
     * Refresh the in-memory database.
     */
    protected function refreshInMemoryDatabase(): void
    {
        $this->artisan('migrate', $this->migrateFreshUsing());
    }

    /**
     * Refresh a conventional test database.
     */
    protected function refreshTestDatabase(): void
    {
        if (! static::$adaptiveDatabaseMigrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());
            static::$adaptiveDatabaseMigrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * The parameters that should be used when running "migrate:fresh".
     */
    protected function migrateFreshUsing(): array
    {
        $seeder = $this->seeder();

        return array_merge(
            [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ],
            $seeder ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()],
        );
    }

    /**
     * Determine if views should be dropped when refreshing the database.
     */
    protected function shouldDropViews(): bool
    {
        return property_exists($this, 'dropViews') ? $this->dropViews : false;
    }

    /**
     * Determine if types should be dropped when refreshing the database.
     */
    protected function shouldDropTypes(): bool
    {
        return property_exists($this, 'dropTypes') ? $this->dropTypes : false;
    }

    /**
     * Determine if the seed task should be run when refreshing the database.
     */
    protected function shouldSeed(): bool
    {
        return property_exists($this, 'seed') ? $this->seed : false;
    }

    /**
     * Get the seeder class name to run.
     */
    protected function seeder(): mixed
    {
        return property_exists($this, 'seeder') ? $this->seeder : false;
    }

    /**
     * Perform any work that should take place before the database has started refreshing.
     */
    protected function beforeRefreshingDatabase(): void
    {
        // Hook for subclasses
    }

    /**
     * Perform any work that should take place once the database has finished refreshing.
     */
    protected function afterRefreshingDatabase(): void
    {
        // Hook for subclasses
    }

    /**
     * Begin a database transaction on the testing database (for local).
     */
    protected function beginDatabaseTransaction(): void
    {
        $database = $this->app->make('db');

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollBack();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });

        foreach ($this->connectionsToTransact() as $name) {
            $database->connection($name)->beginTransaction();
        }
    }
}
