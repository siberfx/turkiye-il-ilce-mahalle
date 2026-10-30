<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TurkiyeAddressServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // Run the real package migrations against the configured schema.
        $this->loadMigrationsFrom(__DIR__ . '/../../src/Database/migrations');
    }

    public function test_service_provider_is_loaded(): void
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(TurkiyeAddressServiceProvider::class),
            'Service provider should be loaded.'
        );
    }

    public function test_turkiye_migrate_command_exists(): void
    {
        // Test that the command exists and can be called without errors
        $this->artisan('turkiye:migrate')->assertExitCode(0);
    }

    public function test_models_exist_and_are_loaded(): void
    {
        $city = new City();
        $district = new District();
        $neighborhood = new Neighborhood();

        $this->assertInstanceOf(City::class, $city);
        $this->assertInstanceOf(District::class, $district);
        $this->assertInstanceOf(Neighborhood::class, $neighborhood);

        // Verify table names are correctly resolved from config
        $this->assertEquals(config('turkiye-package.cities_table', 'cities'), $city->getTable());
        $this->assertEquals(config('turkiye-package.districts_table', 'districts'), $district->getTable());
        $this->assertEquals(config('turkiye-package.neighborhoods_table', 'neighborhoods'), $neighborhood->getTable());
    }

    public function test_model_relationships(): void
    {
        $cityIdColumn = config('turkiye-package.cities_relation_id', 'city_id');
        $districtIdColumn = config('turkiye-package.districts_relation_id', 'district_id');

        $city = City::create([
            'name' => 'Test City',
            'slug' => 'test-city',
        ]);

        $district = District::create([
            $cityIdColumn => $city->id,
            'name' => 'Test District',
            'slug' => 'test-district',
        ]);

        $neighborhood = Neighborhood::create([
            $cityIdColumn => $city->id,
            $districtIdColumn => $district->id,
            'name' => 'Test Neighborhood',
            'slug' => 'test-neighborhood',
        ]);

        // Test relationships
        $this->assertInstanceOf(District::class, $city->districts->first());
        $this->assertInstanceOf(City::class, $district->city);
        $this->assertInstanceOf(Neighborhood::class, $district->neighborhoods->first());
        $this->assertInstanceOf(District::class, $neighborhood->district);

        // Refresh relationships
        $city->load('districts');
        $district->load(['city', 'neighborhoods']);
        $neighborhood->load('district');

        // Assert relationship integrity using dynamic column names
        $this->assertEquals($city->id, $district->{$cityIdColumn});
        $this->assertEquals($district->id, $neighborhood->{$districtIdColumn});

        // Test data values
        $this->assertEquals('Test City', $city->name);
        $this->assertEquals('Test District', $district->name);
        $this->assertEquals('Test Neighborhood', $neighborhood->name);

        // Direct city <-> neighborhood relationships
        $this->assertTrue($city->neighborhoods->first()->is($neighborhood));
        $this->assertTrue($neighborhood->city->is($city));
    }

    public function test_active_scope_and_boolean_cast(): void
    {
        City::create(['name' => 'Active', 'slug' => 'active']);
        City::create(['name' => 'Passive', 'slug' => 'passive', 'is_active' => false]);

        $this->assertSame(['Active'], City::active()->pluck('name')->all());
        $this->assertFalse(City::where('slug', 'passive')->first()->is_active);
    }

    public function test_publish_command_publishes_seeder_and_dumps(): void
    {
        $seeder = database_path('seeders/TurkiyeSeeder.php');

        try {
            $this->artisan('turkiye:publish', ['--seeders' => true])->assertExitCode(0);

            $this->assertFileExists($seeder);
            $this->assertFileExists(database_path('sql-dumps/neighborhoods.sql'));
            $this->assertStringContainsString('namespace Database\Seeders;', file_get_contents($seeder));
        } finally {
            @unlink($seeder);
            File::deleteDirectory(database_path('sql-dumps'));
        }
    }
}
