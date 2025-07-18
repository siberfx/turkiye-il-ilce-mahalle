<?php

namespace Siberfx\TurkiyePackage\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishTurkiyeAssets extends Command
{
    protected $signature = 'turkiye:publish 
                            {--force : Overwrite any existing files}
                            {--config : Publish only the config file}
                            {--migrations : Publish only the migration files}
                            {--seeders : Publish only the seeder files and SQL dumps}';
                            
    protected $description = 'Publish the Türkiye package assets (config, migrations, seeders)';

    public function handle()
    {
        $options = [
            '--provider' => 'Siberfx\\TurkiyePackage\\TurkiyeAdreslerServiceProvider',
            '--force' => $this->option('force'),
        ];

        $tags = [];
        
        // If no specific options are provided, publish everything
        $publishAll = !$this->option('config') && !$this->option('migrations') && !$this->option('seeders');

        if ($publishAll || $this->option('config')) {
            $tags[] = 'config';
        }
        
        if ($publishAll || $this->option('migrations')) {
            $tags[] = 'migrations';
        }
        
        if ($publishAll || $this->option('seeders')) {
            $tags[] = 'seeders';
        }

        if (empty($tags)) {
            $this->error('No publishable resources selected!');
            return 1;
        }

        $options['--tag'] = $tags;

        $this->call('vendor:publish', $options);
        
        // Display success messages based on what was published
        $published = [];
        
        if (in_array('config', $tags)) {
            $published[] = 'Configuration file';
        }
        
        if (in_array('migrations', $tags)) {
            $published[] = 'Migrations';
        }
        
        if (in_array('seeders', $tags)) {
            $published[] = 'Seeders and SQL dumps';
        }
        
        $this->info(sprintf(
            'Successfully published %s!',
            implode(', ', $published)
        ));
        
        return 0;
    }
}
