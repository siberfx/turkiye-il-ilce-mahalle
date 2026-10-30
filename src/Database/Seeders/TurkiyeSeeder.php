<?php

declare(strict_types=1);

namespace Siberfx\TurkiyePackage\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Siberfx\TurkiyePackage\Enums\NeighborhoodType;

class TurkiyeSeeder extends Seeder
{
    /** Rows per INSERT; keeps placeholder count well under every driver's limit. */
    protected int $chunkSize = 500;

    protected string $citiesTable;
    protected string $districtsTable;
    protected string $neighborhoodsTable;
    protected string $citiesRelationId;
    protected string $districtsRelationId;

    public function __construct()
    {
        $this->citiesTable = config('turkiye-package.cities_table', 'cities');
        $this->districtsTable = config('turkiye-package.districts_table', 'districts');
        $this->neighborhoodsTable = config('turkiye-package.neighborhoods_table', 'neighborhoods');
        $this->citiesRelationId = config('turkiye-package.cities_relation_id', 'city_id');
        $this->districtsRelationId = config('turkiye-package.districts_relation_id', 'district_id');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $this->disableForeignKeyChecks();

        try {
            // Truncate in reverse order due to foreign key constraints.
            $this->command?->info('Truncating tables...');
            DB::table($this->neighborhoodsTable)->truncate();
            DB::table($this->districtsTable)->truncate();
            DB::table($this->citiesTable)->truncate();

            DB::transaction(function (): void {
                $this->importCities();
                $this->importDistricts();
                $this->importNeighborhoods();
            });
        } finally {
            $this->enableForeignKeyChecks();
        }
    }

    /**
     * Path to a dump file: the published copy in database/sql-dumps if present,
     * otherwise the one shipped with the package.
     */
    protected function dumpPath(string $file): string
    {
        $published = database_path('sql-dumps/' . $file);

        return File::exists($published) ? $published : __DIR__ . '/../sql-dumps/' . $file;
    }

    /**
     * Parse the VALUES tuples of a dump into rows of unescaped string columns.
     *
     * @return list<list<string>>
     */
    protected function parseDump(string $file, int $columns): array
    {
        $value = "'((?:[^'\\\\]|\\\\.)*)'";
        $pattern = '/\(\s*' . implode('\s*,\s*', array_fill(0, $columns, $value)) . '\s*\)/u';

        preg_match_all($pattern, File::get($this->dumpPath($file)), $matches, PREG_SET_ORDER);

        return array_map(
            fn (array $match): array => array_map(stripslashes(...), array_slice($match, 1)),
            $matches
        );
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

    /**
     * @param  iterable<array<string, mixed>>  $rows
     */
    protected function insertInChunks(string $table, iterable $rows): void
    {
        foreach (collect($rows)->chunk($this->chunkSize) as $chunk) {
            DB::table($table)->insert($chunk->values()->all());
        }
    }

    protected function importCities(): void
    {
        // Format: ('1','ADANA')
        $rows = array_map(fn (array $row): array => [
            'id' => (int) $row[0],
            'name' => $row[1],
            'slug' => Str::slug($row[1]),
            'is_active' => true,
        ], $this->parseDump('cities.sql', 2));

        $this->insertInChunks($this->citiesTable, $rows);

        $this->command?->info(count($rows) . ' cities imported.');
    }

    protected function importDistricts(): void
    {
        // Format: ('1101','37','ABANA')
        $rows = array_map(fn (array $row): array => [
            'id' => (int) $row[0],
            $this->citiesRelationId => (int) $row[1],
            'name' => $row[2],
            'slug' => Str::slug($row[2]),
            'is_active' => true,
        ], $this->parseDump('districts.sql', 3));

        $this->insertInChunks($this->districtsTable, $rows);

        $this->command?->info(count($rows) . ' districts imported.');
    }

    protected function importNeighborhoods(): void
    {
        // district id => city id, to denormalise city_id onto each neighborhood.
        $districtCities = DB::table($this->districtsTable)->pluck($this->citiesRelationId, 'id')->all();

        // Format: ('1','1104','AHMET REMZİ YÜREĞİR MAHALLESİ'), ('4309','1182','AKDURAK KÖYÜ, ÇAKMAK MEZRASI')
        $dump = $this->parseDump('neighborhoods.sql', 3);

        // "district|name" => id of every single-part row, to link a bağlı to its köy.
        $owners = [];

        foreach ($dump as [$id, $districtId, $name]) {
            if (! str_contains($name, ',')) {
                $owners[$districtId . '|' . $name] ??= (int) $id;
            }
        }

        $rows = [];

        foreach ($dump as [$id, $districtId, $name]) {
            // Skip neighborhoods of unknown districts.
            if (! isset($districtCities[$districtId])) {
                continue;
            }

            $type = NeighborhoodType::fromName($name);

            $rows[] = [
                'id' => (int) $id,
                $this->districtsRelationId => (int) $districtId,
                $this->citiesRelationId => (int) $districtCities[$districtId],
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type->value,
                'parent_id' => $type === NeighborhoodType::Bagli ? $this->parentId($owners, $districtId, $name) : null,
                'is_active' => true,
            ];
        }

        $this->insertInChunks($this->neighborhoodsTable, $rows);

        $this->command?->info(count($rows) . ' neighborhoods imported.');
    }

    /**
     * The köy row a bağlı ("X KÖYÜ, Y ...") belongs to; falls back to a same-named
     * mahalle for köys that became mahalles in metropolitan provinces.
     *
     * @param  array<string, int>  $owners
     */
    protected function parentId(array $owners, string $districtId, string $name): ?int
    {
        // Only "X KÖYÜ, Y ..." names point at a parent; a standalone bağlı has none.
        if (! str_contains($name, ',')) {
            return null;
        }

        $head = trim(explode(',', $name, 2)[0]);

        return $owners[$districtId . '|' . $head]
            ?? (str_ends_with($head, ' KÖYÜ')
                ? $owners[$districtId . '|' . substr($head, 0, -strlen(' KÖYÜ')) . ' MAHALLESİ'] ?? null
                : null);
    }
}
