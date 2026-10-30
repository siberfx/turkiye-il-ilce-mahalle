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
     *
     * Rows are upserted by id, never truncated or deleted: re-running the seeder adds new
     * records and refreshes existing ones in place, so foreign keys from application tables
     * (e.g. an addresses table) stay valid and their rows are untouched. `is_active` is only
     * set for new rows, so records you deactivated stay deactivated.
     *
     * Parents are always written before their children (cities, districts, köy/mahalle,
     * then bağlı), so foreign key checks can stay enabled on every driver.
     */
    public function run(): void
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        DB::transaction(function (): void {
            $this->importCities();
            $this->importDistricts();
            $this->importNeighborhoods();
        });

        $this->syncSequences();
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

    /**
     * Insert new rows and update existing ones by id; `is_active` is never overwritten.
     *
     * @param  iterable<array<string, mixed>>  $rows
     */
    protected function upsertInChunks(string $table, iterable $rows): void
    {
        foreach (collect($rows)->chunk($this->chunkSize) as $chunk) {
            $chunk = $chunk->values()->all();
            $update = array_values(array_diff(array_keys($chunk[0]), ['id', 'is_active']));

            DB::table($table)->upsert($chunk, ['id'], $update);
        }
    }

    /**
     * PostgreSQL does not advance an id sequence for explicit ids; move each sequence past
     * the highest id so the application can keep creating rows.
     */
    protected function syncSequences(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ([$this->citiesTable, $this->districtsTable, $this->neighborhoodsTable] as $table) {
            $table = DB::connection()->getTablePrefix() . $table;

            DB::statement(
                "select setval(pg_get_serial_sequence(?, 'id'), coalesce((select max(id) from \"{$table}\"), 1))",
                [$table]
            );
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

        $this->upsertInChunks($this->citiesTable, $rows);

        $this->command?->info(count($rows) . ' cities imported.');
    }

    protected function importDistricts(): void
    {
        $cityIds = array_flip(DB::table($this->citiesTable)->pluck('id')->all());

        // Format: ('1101','37','ABANA'); districts of unknown cities are skipped.
        $rows = [];

        foreach ($this->parseDump('districts.sql', 3) as [$id, $cityId, $name]) {
            if (! isset($cityIds[(int) $cityId])) {
                continue;
            }

            $rows[] = [
                'id' => (int) $id,
                $this->citiesRelationId => (int) $cityId,
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
            ];
        }

        $this->upsertInChunks($this->districtsTable, $rows);

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

        // Köy and mahalle before bağlı, so every parent_id points at an existing row.
        usort($rows, fn (array $a, array $b): int => ($a['parent_id'] !== null) <=> ($b['parent_id'] !== null));

        $this->upsertInChunks($this->neighborhoodsTable, $rows);

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
