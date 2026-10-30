<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Console\Commands;

use Illuminate\Console\Command;
use Siberfx\TurkiyePackage\Support\CityOrder;

class SortCitiesCommand extends Command
{
    protected $signature = 'turkiye:sort-cities
                            {cities?* : Plate codes to put first, e.g. 34 6 35 16 (defaults to config featured_cities)}';

    protected $description = 'Recalculate cities.sort_order: featured cities first, then Turkish alphabetical order';

    public function handle(): int
    {
        $featured = $this->argument('cities') ?: null;

        $count = CityOrder::apply($featured === null ? null : array_map(intval(...), $featured));

        $this->components->info("Sort order updated for {$count} cities.");

        return self::SUCCESS;
    }
}
