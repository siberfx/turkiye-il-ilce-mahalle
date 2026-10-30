<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Console\Commands;

use Illuminate\Console\Command;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;

class MigrateTurkiyeCommand extends Command
{
    protected $signature = 'turkiye:migrate
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed the address tables after migrating}
                            {--force : Force the operation to run when in production}';

    protected $description = 'Run the Türkiye package migrations';

    public function handle(): int
    {
        $options = [
            '--path' => realpath(__DIR__ . '/../../Database/migrations'),
            '--realpath' => true,
            '--force' => $this->option('force'),
        ];

        $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate', $options);

        if ($this->option('seed')) {
            $this->call('db:seed', [
                '--class' => TurkiyeSeeder::class,
                '--force' => $this->option('force'),
            ]);
        }

        $this->components->info('Türkiye package migrations completed successfully.');

        return self::SUCCESS;
    }
}
