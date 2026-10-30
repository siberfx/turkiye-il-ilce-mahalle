<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\Neighborhood;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

class CustomConfigurationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TurkiyeAddressServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('turkiye-package.cities_table', 'tr_iller');
        $app['config']->set('turkiye-package.districts_table', 'tr_ilceler');
        $app['config']->set('turkiye-package.neighborhoods_table', 'tr_mahalleler');
        $app['config']->set('turkiye-package.cities_relation_id', 'il_id');
        $app['config']->set('turkiye-package.districts_relation_id', 'ilce_id');
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

    public function test_migrations_use_configured_tables_and_columns(): void
    {
        $this->assertTrue(Schema::hasTable('tr_iller'));
        $this->assertTrue(Schema::hasColumns('tr_ilceler', ['il_id']));
        $this->assertTrue(Schema::hasColumns('tr_mahalleler', ['il_id', 'ilce_id', 'type', 'parent_id']));
        $this->assertFalse(Schema::hasTable('cities'));
    }

    public function test_seeder_and_models_follow_the_configuration(): void
    {
        File::ensureDirectoryExists(database_path('sql-dumps'));
        File::put(database_path('sql-dumps/cities.sql'), "INSERT INTO `cities` (`id`, `name`) VALUES\n('2','ADIYAMAN');");
        File::put(database_path('sql-dumps/districts.sql'), "INSERT INTO `districts` (`id`, `city_id`, `name`) VALUES\n('1182','2','BESNİ');");
        File::put(database_path('sql-dumps/neighborhoods.sql'), "INSERT INTO `neighborhoods` (`id`, `district_id`, `name`) VALUES\n"
            . "('4308','1182','AKDURAK KÖYÜ'),\n('4310','1182','AKDURAK KÖYÜ, ÇAKMAK MEZRASI'),\n('476','1182','ATATÜRK MAHALLESİ');");

        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $this->assertSame(3, DB::table('tr_mahalleler')->count());
        $this->assertSame(4308, DB::table('tr_mahalleler')->where('id', 4310)->value('parent_id'));
        $this->assertSame(2, DB::table('tr_mahalleler')->where('id', 4310)->value('il_id'));

        $city = City::find(2);
        $this->assertSame('tr_iller', $city->getTable());
        $this->assertSame(['BESNİ'], $city->districts->pluck('name')->all());
        $this->assertSame(['AKDURAK KÖYÜ'], $city->districts->first()->villages->pluck('name')->all());
        $this->assertSame('AKDURAK KÖYÜ', Neighborhood::find(4310)->parent->name);
        $this->assertSame('ADIYAMAN', Neighborhood::find(476)->city->name);
    }
}
