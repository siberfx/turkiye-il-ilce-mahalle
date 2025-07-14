<?php

namespace Siberfx\TurkiyePackage\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TurkiyeSeeder extends Seeder
{
    public function run(): void
    {
        // Increase timeout and memory for large imports
        ini_set('max_execution_time', 0); // Unlimited
        ini_set('memory_limit', '-1');
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $config = config('turkiye-adresler');
        $citiesTable = $config['cities_table'] ?? 'cities';
        $districtsTable = $config['districts_table'] ?? 'districts';
        $neighborhoodsTable = $config['neighborhoods_table'] ?? 'neighborhoods';

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Import cities
        $citiesSql = File::get(base_path('src/database/sql-dumps/cities.sql'));
        $citiesSql = str_replace('INSERT INTO `cities`', 'INSERT IGNORE INTO `'.$citiesTable.'`', $citiesSql);
        DB::unprepared($citiesSql);

        // Import districts
        $districtsSql = File::get(base_path('src/database/sql-dumps/districts.sql'));
        $districtsSql = str_replace('INSERT INTO `districts`', 'INSERT IGNORE INTO `'.$districtsTable.'`', $districtsSql);
        DB::unprepared($districtsSql);

        // Import neighborhoods
        $neighborhoodsSql = File::get(base_path('src/database/sql-dumps/neighborhoods.sql'));
        $neighborhoodsSql = str_replace('INSERT INTO `neighborhoods`', 'INSERT IGNORE INTO `'.$neighborhoodsTable.'`', $neighborhoodsSql);
        DB::unprepared($neighborhoodsSql);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}