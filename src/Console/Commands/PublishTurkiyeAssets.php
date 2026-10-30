<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Console\Commands;

use Illuminate\Console\Command;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

class PublishTurkiyeAssets extends Command
{
    protected $signature = 'turkiye:publish
                            {--force : Overwrite any existing files}
                            {--config : Publish only the config file}
                            {--migrations : Publish only the migration files}
                            {--seeders : Publish only the seeder and SQL dumps}';

    protected $description = 'Publish the Türkiye package assets (config, migrations, seeders)';

    /** @var array<string, string> tag => label */
    private const array TAGS = [
        'config' => 'Configuration file',
        'migrations' => 'Migrations',
        'seeders' => 'Seeder and SQL dumps',
    ];

    public function handle(): int
    {
        $selected = array_filter(array_keys(self::TAGS), fn (string $tag): bool => (bool) $this->option($tag));

        // No specific option given: publish everything.
        $tags = $selected ?: array_keys(self::TAGS);

        $this->call('vendor:publish', [
            '--provider' => TurkiyeAddressServiceProvider::class,
            '--tag' => $tags,
            '--force' => $this->option('force'),
        ]);

        $this->components->info(sprintf(
            'Published: %s.',
            implode(', ', array_map(fn (string $tag): string => self::TAGS[$tag], $tags))
        ));

        return self::SUCCESS;
    }
}
