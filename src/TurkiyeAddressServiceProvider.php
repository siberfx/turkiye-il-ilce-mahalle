<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage;

use Illuminate\Support\ServiceProvider;
use Siberfx\TurkiyePackage\Console\Commands\MigrateTurkiyeCommand;
use Siberfx\TurkiyePackage\Console\Commands\PublishTurkiyeAssets;

class TurkiyeAddressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/turkiye-package.php', 'turkiye-package');
    }

    public function boot(): void
    {
        // Load package migrations so they run without publishing.
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/turkiye-package.php' => config_path('turkiye-package.php'),
        ], 'config');

        $this->publishesMigrations([
            __DIR__ . '/Database/migrations' => database_path('migrations'),
        ], 'migrations');

        // A thin app-level seeder extending the package seeder, plus the SQL dumps
        // so they can be edited locally (the seeder prefers the published copies).
        $this->publishes([
            __DIR__ . '/stubs/turkiye-seeder.stub' => database_path('seeders/TurkiyeSeeder.php'),
            __DIR__ . '/Database/sql-dumps' => database_path('sql-dumps'),
        ], 'seeders');

        $this->commands([
            PublishTurkiyeAssets::class,
            MigrateTurkiyeCommand::class,
        ]);
    }
}
