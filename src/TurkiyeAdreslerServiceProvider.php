<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage;

use Illuminate\Support\ServiceProvider;
use Siberfx\TurkiyePackage\Console\Commands\PublishTurkiyeAssets;
use Siberfx\TurkiyePackage\Console\Commands\MigrateTurkiyeCommand;

class TurkiyeAdreslerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load package migrations so they run without publishing.
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/turkiye-package.php' => config_path('turkiye-package.php'),
        ], 'config');

        // Publish migrations (optional)
        $this->publishes([
            __DIR__ . '/Database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Publish seeders and SQL dumps (optional)
        $this->publishes([
            __DIR__ . '/Database/Seeders/TurkiyeSeeder.php' => database_path('seeders/TurkiyeSeeder.php'),
            __DIR__ . '/Database/sql-dumps' => database_path('sql-dumps'),
        ], 'seeders');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/turkiye-package.php', 'turkiye-package'
        );

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishTurkiyeAssets::class,
                MigrateTurkiyeCommand::class,
            ]);
        }
    }
}
