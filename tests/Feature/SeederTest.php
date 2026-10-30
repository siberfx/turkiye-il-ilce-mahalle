<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

class SeederTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TurkiyeAddressServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../src/Database/migrations');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(database_path('sql-dumps'));

        parent::tearDown();
    }

    public function test_seeder_imports_bundled_dumps_with_referential_integrity(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertDirectoryDoesNotExist(database_path('sql-dumps'));

        (new TurkiyeSeeder())->setContainer($this->app)->run();

        // Türkiye has 81 provinces and 973 districts.
        $this->assertSame(81, DB::table('cities')->count());
        $this->assertSame(973, DB::table('districts')->count());
        $this->assertSame(74398, DB::table('neighborhoods')->count());

        // Every district points at an existing city.
        $orphanDistricts = DB::table('districts')
            ->whereNotIn('city_id', DB::table('cities')->select('id'))
            ->count();
        $this->assertSame(0, $orphanDistricts, 'Districts must reference an existing city.');

        // Every neighborhood's denormalised city_id matches its district's city_id.
        $mismatched = DB::table('neighborhoods as n')
            ->join('districts as d', 'n.district_id', '=', 'd.id')
            ->whereColumn('n.city_id', '!=', 'd.city_id')
            ->count();
        $this->assertSame(0, $mismatched, 'Neighborhood city_id must match its district city_id.');
    }

    public function test_seeder_unescapes_quoted_names(): void
    {
        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $this->assertSame(
            "BOĞAZİÇİ KÖYÜ, TAN'IN KOMLARI MEVKİİ",
            DB::table('neighborhoods')->where('id', 29235)->value('name')
        );
    }

    public function test_seeder_prefers_published_dumps(): void
    {
        File::ensureDirectoryExists(database_path('sql-dumps'));
        File::put(database_path('sql-dumps/cities.sql'), "INSERT INTO `cities` (`id`, `name`) VALUES\n('1','ADANA');");
        File::put(database_path('sql-dumps/districts.sql'), "INSERT INTO `districts` (`id`, `city_id`, `name`) VALUES\n('1104','1','SEYHAN');");
        File::put(database_path('sql-dumps/neighborhoods.sql'), "INSERT INTO `neighborhoods` (`id`, `district_id`, `name`) VALUES\n('1','1104','AKKAPI MAHALLESİ'),\n('2','9999','ORPHAN MAHALLESİ');");

        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $this->assertSame(1, DB::table('cities')->count());
        $this->assertSame(1, DB::table('districts')->count());
        // Neighborhoods of unknown districts are skipped.
        $this->assertSame(1, DB::table('neighborhoods')->count());
        $this->assertSame(1, DB::table('neighborhoods')->where('id', 1)->value('city_id'));
    }
}
