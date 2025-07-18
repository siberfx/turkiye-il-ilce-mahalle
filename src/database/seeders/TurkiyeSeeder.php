<?php

namespace Siberfx\TurkiyePackage\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TurkiyeSeeder extends Seeder
{
    protected $config;
    protected $cityModel;
    protected $districtModel;
    protected $neighborhoodModel;

    public function __construct()
    {
        $this->config = config('turkiye-adresler');
        
        // Set model class names from config or use package defaults
        $this->cityModel = $this->config['city_model'] ?? 'Siberfx\\TurkiyePackage\\Models\\City';
        $this->districtModel = $this->config['district_model'] ?? 'Siberfx\\TurkiyePackage\\Models\\District';
        $this->neighborhoodModel = $this->config['neighborhood_model'] ?? 'Siberfx\\TurkiyePackage\\Models\\Neighborhood';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Increase timeout and memory for large imports
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        // Disable foreign key checks and truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Get table names from config or use defaults
        $citiesTable = $this->config['cities_table'] ?? 'cities';
        $districtsTable = $this->config['districts_table'] ?? 'districts';
        $neighborhoodsTable = $this->config['neighborhoods_table'] ?? 'neighborhoods';

        // Truncate tables in reverse order due to foreign key constraints
        $this->command->info('Truncating tables...');
        DB::table($neighborhoodsTable)->truncate();
        DB::table($districtsTable)->truncate();
        DB::table($citiesTable)->truncate();
        
        // Reset auto-increment values
        $this->resetAutoIncrement($citiesTable);
        $this->resetAutoIncrement($districtsTable);
        $this->resetAutoIncrement($neighborhoodsTable);

        // Import data
        $this->importCities();
        $this->importDistricts();
        $this->importNeighborhoods();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function resetAutoIncrement($table): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        }
    }

    protected function getCityModel()
    {
        return app($this->cityModel);
    }

    protected function getDistrictModel()
    {
        return app($this->districtModel);
    }

    protected function getNeighborhoodModel()
    {
        return app($this->neighborhoodModel);
    }

    private function importCities(): void
    {
        $table = $this->config['cities_table'] ?? 'cities';
        $citiesSql = File::get(database_path('sql-dumps/cities.sql'));
        
        // Extract data from SQL - match format: ('1','ADANA')
        preg_match_all("/\('(\d+)'\s*,\s*'([^']+)'\s*\)/i", $citiesSql, $matches);

        $cities = [];
        $now = now();

        foreach ($matches[1] as $index => $id) {
            $name = $matches[2][$index];
            $cities[] = [
                'id' => $id,
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($cities) >= 100) {
                DB::table($table)->insert($cities);
                $cities = [];
            }
        }

        if (!empty($cities)) {
            DB::table($table)->insert($cities);
        }

        $this->command->info('Cities imported successfully.');
    }

    private function importDistricts(): void
    {
        $table = $this->config['districts_table'] ?? 'districts';
        $districtsSql = File::get(database_path('sql-dumps/districts.sql'));
        
        // Extract data from SQL - match format: ('1101','37','ABANA')
        preg_match_all("/\('(\d+)'\s*,\s*'(\d+)'\s*,\s*'([^']+)'\s*\)/i", $districtsSql, $matches);

        $districts = [];
        $now = now();

        foreach ($matches[1] as $index => $id) {
            $cityId = $matches[2][$index];
            $name = $matches[3][$index];

            $districts[] = [
                'id' => $id,
                Str::singular($table).'_id' => $cityId,
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($districts) >= 100) {
                DB::table($table)->insert($districts);
                $districts = [];
            }
        }

        if (!empty($districts)) {
            DB::table($table)->insert($districts);
        }

        $this->command->info('Districts imported successfully.');
    }

    private function importNeighborhoods(): void
    {
        $table = $this->config['neighborhoods_table'] ?? 'neighborhoods';
        $districtsTable = $this->config['districts_table'] ?? 'districts';
        $neighborhoodsSql = File::get(database_path('sql-dumps/neighborhoods.sql'));
        
        // Get all districts with their city_id
        $districts = DB::table($districtsTable)->pluck('city_id', 'id');
        
        // Extract data from SQL - match format: ('1','1104','AHMET REMZİ YÜREĞİR MAHALLESİ')
        preg_match_all("/\('(\d+)'\s*,\s*'(\d+)'\s*,\s*'([^']+)'\s*\)/i", $neighborhoodsSql, $matches);

        $neighborhoods = [];
        $now = now();

        foreach ($matches[1] as $index => $id) {
            $districtId = $matches[2][$index];
            $name = $matches[3][$index];

            // Skip if district doesn't exist
            if (!isset($districts[$districtId])) {
                continue;
            }

            $neighborhoods[] = [
                'id' => $id,
                Str::singular($districtsTable).'_id' => $districtId,
                Str::singular($this->config['cities_table']).'_id' => $districts[$districtId],
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($neighborhoods) >= 100) {
                DB::table($table)->insert($neighborhoods);
                $neighborhoods = [];
            }
        }

        if (!empty($neighborhoods)) {
            DB::table($table)->insert($neighborhoods);
        }

        $this->command->info('Neighborhoods imported successfully.');
    }
}
