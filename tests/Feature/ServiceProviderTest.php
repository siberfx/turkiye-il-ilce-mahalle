<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        // Set the configuration to match our test schema
        config([
            'turkiye-package.cities_table' => 'cities',
            'turkiye-package.districts_table' => 'districts',
            'turkiye-package.neighborhoods_table' => 'neighborhoods',
            'turkiye-package.cities_relation_id' => 'city_id',
            'turkiye-package.districts_relation_id' => 'district_id',
            'turkiye-package.neighborhoods_relation_id' => 'neighborhood_id',
        ]);

        // Create test tables for our models with the expected schema
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId(config('turkiye-package.cities_relation_id', 'city_id'))
                  ->constrained('cities');
            $table->string('name');
        });

        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId(config('turkiye-package.districts_relation_id', 'district_id'))
                  ->constrained('districts');
            $table->string('name');
        });
    }

    public function test_service_provider_is_loaded()
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(\Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider::class),
            'Service provider should be loaded.'
        );
    }

    public function test_turkiye_migrate_command_exists()
    {
        // Test that the command exists and can be called without errors
        $this->artisan('turkiye:migrate')->assertExitCode(0);
    }

    public function test_models_exist_and_are_loaded()
    {
        // Test that the models exist and can be instantiated
        $city = new City();
        $district = new District();
        $neighborhood = new Neighborhood();
        
        $this->assertInstanceOf(City::class, $city);
        $this->assertInstanceOf(District::class, $district);
        $this->assertInstanceOf(Neighborhood::class, $neighborhood);
        
        // Verify table names are correctly set from config
        $this->assertEquals(config('turkiye-package.cities_table', 'cities'), $city->getTable());
        $this->assertEquals(config('turkiye-package.districts_table', 'districts'), $district->getTable());
        $this->assertEquals(config('turkiye-package.neighborhoods_table', 'neighborhoods'), $neighborhood->getTable());
    }
    
    public function test_model_relationships()
    {
        // Get relationship column names from config
        $cityIdColumn = config('turkiye-package.cities_relation_id', 'city_id');
        $districtIdColumn = config('turkiye-package.districts_relation_id', 'district_id');
        
        // Create test data using dynamic column names
        $city = City::create(['name' => 'Test City']);
        $district = District::create([
            $cityIdColumn => $city->id, 
            'name' => 'Test District'
        ]);
        $neighborhood = Neighborhood::create([
            $districtIdColumn => $district->id, 
            'name' => 'Test Neighborhood'
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
    }
}
