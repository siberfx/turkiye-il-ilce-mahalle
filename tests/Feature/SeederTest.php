<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;
use Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider;

class SeederTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TurkiyeAdreslerServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../src/Database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The seeder reads dumps from database_path('sql-dumps'); stage them there.
        File::ensureDirectoryExists(database_path('sql-dumps'));
        File::copyDirectory(__DIR__ . '/../../src/Database/sql-dumps', database_path('sql-dumps'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(database_path('sql-dumps'));

        parent::tearDown();
    }

    public function test_seeder_imports_data_with_referential_integrity_on_sqlite(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        (new TurkiyeSeeder())->setContainer($this->app)->run();

        // Türkiye has 81 provinces.
        $this->assertSame(81, DB::table('cities')->count());
        $this->assertGreaterThan(900, DB::table('districts')->count());
        $this->assertGreaterThan(40000, DB::table('neighborhoods')->count());

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
}
