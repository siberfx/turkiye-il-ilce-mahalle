<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\Neighborhood;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

/**
 * Re-seeding an existing installation (e.g. after upgrading the package) must not
 * touch application data that references the address tables.
 */
class ReseedTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TurkiyeAddressServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../src/Database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::enableForeignKeyConstraints();

        // An application table that cascades from the package tables.
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('neighborhood_id')->constrained('neighborhoods')->cascadeOnDelete();
            $table->string('line');
        });

        $this->writeDumps(
            cities: "('2','ADIYAMAN')",
            districts: "('1182','2','BESNİ')",
            // The bağlı comes before its köy on purpose: parents must still be written first.
            neighborhoods: "('4310','1182','AKDURAK KÖYÜ, ÇAKMAK MEZRASI'),\n('4308','1182','AKDURAK KÖYÜ'),\n('476','1182','ATATÜRK MAHALLESİ')",
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('addresses');
        File::deleteDirectory(database_path('sql-dumps'));

        parent::tearDown();
    }

    public function test_seeding_with_foreign_keys_enforced_writes_parents_first(): void
    {
        $this->runSeeder();

        $this->assertSame(3, DB::table('neighborhoods')->count());
        $this->assertSame(4308, DB::table('neighborhoods')->where('id', 4310)->value('parent_id'));
    }

    public function test_reseeding_keeps_application_rows_and_deactivations(): void
    {
        $this->runSeeder();

        DB::table('addresses')->insert(['city_id' => 2, 'neighborhood_id' => 476, 'line' => 'No: 1']);
        Neighborhood::whereKey(4310)->update(['is_active' => false]);

        // A newer dump: a renamed record and a new köy with a bağlı.
        $this->writeDumps(
            cities: "('2','ADIYAMAN')",
            districts: "('1182','2','BESNİ')",
            neighborhoods: "('4310','1182','AKDURAK KÖYÜ, ÇAKMAK MEZRASI'),\n('4308','1182','AKDURAK KÖYÜ'),\n"
                . "('476','1182','ATATÜRK MAHALLESİ (YENİ)'),\n('1005565','1182','MUTLU KÖYÜ, ÇUBUKLU'),\n('1005564','1182','MUTLU KÖYÜ')",
        );
        $this->runSeeder();

        // Application data survived, on every driver (no TRUNCATE ... CASCADE).
        $this->assertSame(1, DB::table('addresses')->count());

        // Existing rows were refreshed in place, new rows were added and linked.
        $this->assertSame('ATATÜRK MAHALLESİ (YENİ)', Neighborhood::find(476)->name);
        $this->assertSame(1005564, Neighborhood::find(1005565)->parent_id);
        $this->assertSame(5, DB::table('neighborhoods')->count());

        // A record the application deactivated stays deactivated.
        $this->assertFalse(Neighborhood::find(4310)->is_active);
        $this->assertTrue(Neighborhood::find(1005565)->is_active);
    }

    public function test_application_can_create_rows_after_seeding(): void
    {
        $this->runSeeder();

        // On PostgreSQL the id sequences must have been moved past the seeded ids.
        $city = City::create(['name' => 'YENİ İL', 'slug' => 'yeni-il']);

        $this->assertGreaterThan(2, $city->id);
    }

    public function test_districts_of_unknown_cities_are_skipped(): void
    {
        $this->writeDumps(
            cities: "('2','ADIYAMAN')",
            districts: "('1182','2','BESNİ'),\n('9999','99','NOWHERE')",
            neighborhoods: "('476','1182','ATATÜRK MAHALLESİ'),\n('1','9999','ORPHAN MAHALLESİ')",
        );

        $this->runSeeder();

        $this->assertSame([1182], DB::table('districts')->pluck('id')->all());
        $this->assertSame([476], DB::table('neighborhoods')->pluck('id')->all());
    }

    private function runSeeder(): void
    {
        (new TurkiyeSeeder())->setContainer($this->app)->run();
    }

    private function writeDumps(string $cities, string $districts, string $neighborhoods): void
    {
        File::ensureDirectoryExists(database_path('sql-dumps'));
        File::put(database_path('sql-dumps/cities.sql'), "INSERT INTO `cities` (`id`, `name`) VALUES\n{$cities};");
        File::put(database_path('sql-dumps/districts.sql'), "INSERT INTO `districts` (`id`, `city_id`, `name`) VALUES\n{$districts};");
        File::put(database_path('sql-dumps/neighborhoods.sql'), "INSERT INTO `neighborhoods` (`id`, `district_id`, `name`) VALUES\n{$neighborhoods};");
    }
}
