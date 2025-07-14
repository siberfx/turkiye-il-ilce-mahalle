<?php

namespace Siberfx\TurkiyePackage;

use Illuminate\Support\ServiceProvider;

class TurkiyeAdreslerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/turkiye-package.php' => config_path('turkiye-package.php'),
        ], 'config');

        // Publish seeders and SQL dumps (optional)
        $this->publishes([
            __DIR__.'/database/seeders/TurkiyeSeeder.php' => database_path('seeders/TurkiyeSeeder.php'),
            __DIR__.'/database/sql-dumps' => database_path('sql-dumps'),
        ], 'seeders');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/turkiye-package.php', 'turkiye-adresler'
        );
    }
}
