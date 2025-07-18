<?php

namespace Siberfx\TurkiyePackage\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateTurkiyeCommand extends Command
{
    protected $signature = 'turkiye:migrate {--fresh : Drop all tables and re-run all migrations} 
                                         {--seed : Seed the database after migration}';
    
    protected $description = 'Run the database migrations for the Türkiye package';

    public function handle()
    {
        $options = ['--path' => 'database/migrations'];
        
        if ($this->option('fresh')) {
            $this->call('migrate:fresh', $options);
        } else {
            $this->call('migrate', $options);
        }

        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => 'TurkiyeSeeder']);
        }

        $this->info('Türkiye package migrations completed successfully.');
        
        return 0;
    }
}
