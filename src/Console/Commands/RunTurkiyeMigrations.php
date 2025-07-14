<?php

namespace Siberfx\TurkiyePackage\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunTurkiyeMigrations extends Command
{
    protected $signature = 'turkiye:migrate';
    protected $description = 'Run the Turkish address package migration files.';

    public function handle(): int
    {
        $this->info('Running Turkish address package migrations...');
        Artisan::call('migrate', [
            '--path' => 'src/database/migrations',
            '--realpath' => true,
        ]);
        $this->info(Artisan::output());
        $this->info('Migrations complete.');
        return self::SUCCESS;
    }
}
