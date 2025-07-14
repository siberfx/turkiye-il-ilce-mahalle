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
        // Create test tables for our models
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained();
            $table->string('name');
        });

        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained();
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
        $this->assertEquals(config('turkiye-adresler.cities_table', 'cities'), $city->getTable());
        $this->assertEquals(config('turkiye-adresler.districts_table', 'districts'), $district->getTable());
        $this->assertEquals(config('turkiye-adresler.neighborhoods_table', 'neighborhoods'), $neighborhood->getTable());
    }
    
    public function test_model_relationships()
    {
        // Create test data
        $city = City::create(['name' => 'Test City']);
        $district = District::create(['city_id' => $city->id, 'name' => 'Test District']);
        $neighborhood = Neighborhood::create([
            'district_id' => $district->id, 
            'name' => 'Test Neighborhood'
        ]);
        
        // Test relationships
        $this->assertInstanceOf(District::class, $city->districts->first());
        $this->assertInstanceOf(City::class, $district->city);
        $this->assertInstanceOf(Neighborhood::class, $district->neighborhoods->first());
        $this->assertInstanceOf(District::class, $neighborhood->district);
        
        // Test data values
        $this->assertEquals('Test City', $city->name);
        $this->assertEquals('Test District', $district->name);
        $this->assertEquals('Test Neighborhood', $neighborhood->name);
    }
}
