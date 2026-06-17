<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;

class TurkiyeSeeder extends Seeder
{
    protected array $config;
    protected string $cityModel;
    protected string $districtModel;
    protected string $neighborhoodModel;
    protected string $citiesTable;
    protected string $districtsTable;
    protected string $neighborhoodsTable;
    protected string $citiesRelationId;
    protected string $districtsRelationId;

    public function __construct()
    {
        $this->config = config('turkiye-package', []);

        // Set model class names from config or use package defaults
        $this->cityModel = $this->config['city_model'] ?? City::class;
        $this->districtModel = $this->config['district_model'] ?? District::class;
        $this->neighborhoodModel = $this->config['neighborhood_model'] ?? Neighborhood::class;

        $this->citiesTable = $this->config['cities_table'] ?? 'cities';
        $this->districtsTable = $this->config['districts_table'] ?? 'districts';
        $this->neighborhoodsTable = $this->config['neighborhoods_table'] ?? 'neighborhoods';
        $this->citiesRelationId = $this->config['cities_relation_id'] ?? 'city_id';
        $this->districtsRelationId = $this->config['districts_relation_id'] ?? 'district_id';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Increase timeout and memory for large imports
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        // Disable foreign key checks (driver-aware)
        $this->disableForeignKeyChecks();

        // Truncate tables in reverse order due to foreign key constraints
        $this->command?->info('Truncating tables...');
        DB::table($this->neighborhoodsTable)->truncate();
        DB::table($this->districtsTable)->truncate();
        DB::table($this->citiesTable)->truncate();

        // Reset auto-increment values
        $this->resetAutoIncrement($this->citiesTable);
        $this->resetAutoIncrement($this->districtsTable);
        $this->resetAutoIncrement($this->neighborhoodsTable);

        // Import data
        $this->importCities();
        $this->importDistricts();
        $this->importNeighborhoods();

        $this->enableForeignKeyChecks();
    }

    protected function disableForeignKeyChecks(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=0;'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF;'),
            'pgsql' => DB::statement('SET session_replication_role = replica;'),
            default => null,
        };
    }

    protected function enableForeignKeyChecks(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=1;'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON;'),
            'pgsql' => DB::statement('SET session_replication_role = DEFAULT;'),
            default => null,
        };
    }

    protected function resetAutoIncrement(string $table): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        }
    }

    protected function getCityModel(): mixed
    {
        return app($this->cityModel);
    }

    protected function getDistrictModel(): mixed
    {
        return app($this->districtModel);
    }

    protected function getNeighborhoodModel(): mixed
    {
        return app($this->neighborhoodModel);
    }

    private function importCities(): void
    {
        $citiesSql = File::get(database_path('sql-dumps/cities.sql'));

        // Extract data from SQL - match format: ('1','ADANA')
        preg_match_all("/\('(\d+)'\s*,\s*'([^']+)'\s*\)/i", $citiesSql, $matches);

        $cities = [];

        foreach ($matches[1] as $index => $id) {
            $name = $matches[2][$index];
            $cities[] = [
                'id' => $id,
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
            ];

            if (count($cities) >= 100) {
                DB::table($this->citiesTable)->insert($cities);
                $cities = [];
            }
        }

        if (! empty($cities)) {
            DB::table($this->citiesTable)->insert($cities);
        }

        $this->command?->info('Cities imported successfully.');
    }

    private function importDistricts(): void
    {
        $districtsSql = File::get(database_path('sql-dumps/districts.sql'));

        // Extract data from SQL - match format: ('1101','37','ABANA')
        preg_match_all("/\('(\d+)'\s*,\s*'(\d+)'\s*,\s*'([^']+)'\s*\)/i", $districtsSql, $matches);

        $districts = [];

        foreach ($matches[1] as $index => $id) {
            $cityId = $matches[2][$index];
            $name = $matches[3][$index];

            $districts[] = [
                'id' => $id,
                $this->citiesRelationId => $cityId,
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
            ];

            if (count($districts) >= 100) {
                DB::table($this->districtsTable)->insert($districts);
                $districts = [];
            }
        }

        if (! empty($districts)) {
            DB::table($this->districtsTable)->insert($districts);
        }

        $this->command?->info('Districts imported successfully.');
    }

    private function importNeighborhoods(): void
    {
        $neighborhoodsSql = File::get(database_path('sql-dumps/neighborhoods.sql'));

        // Get all districts with their city id
        $districts = DB::table($this->districtsTable)->pluck($this->citiesRelationId, 'id');

        // Extract data from SQL - match format: ('1','1104','AHMET REMZİ YÜREĞİR MAHALLESİ')
        preg_match_all("/\('(\d+)'\s*,\s*'(\d+)'\s*,\s*'([^']+)'\s*\)/i", $neighborhoodsSql, $matches);

        $neighborhoods = [];

        foreach ($matches[1] as $index => $id) {
            $districtId = $matches[2][$index];
            $name = $matches[3][$index];

            // Skip if district doesn't exist
            if (! isset($districts[$districtId])) {
                continue;
            }

            $neighborhoods[] = [
                'id' => $id,
                $this->districtsRelationId => $districtId,
                $this->citiesRelationId => $districts[$districtId],
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
            ];

            if (count($neighborhoods) >= 100) {
                DB::table($this->neighborhoodsTable)->insert($neighborhoods);
                $neighborhoods = [];
            }
        }

        if (! empty($neighborhoods)) {
            DB::table($this->neighborhoodsTable)->insert($neighborhoods);
        }

        $this->command?->info('Neighborhoods imported successfully.');
    }
}
