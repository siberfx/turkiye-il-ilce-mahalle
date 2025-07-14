<?php

namespace Siberfx\TurkiyePackage\Console\Commands;

use Illuminate\Console\Command;

class PublishTurkiyeAssets extends Command
{
    protected $signature = 'turkiye:publish-assets {--force : Overwrite any existing files}';
    protected $description = 'Publish the Türkiye package config file and SQL dumps folder';

    public function handle()
    {
        $this->call('vendor:publish', [
            '--provider' => 'Siberfx\\TurkiyePackage\\TurkiyeAdreslerServiceProvider',
            '--tag' => ['config', 'seeders'],
            '--force' => $this->option('force'),
        ]);
        $this->info('Türkiye package config and SQL dumps published!');
    }
}
