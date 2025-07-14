<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider::class,
        ];
    }

    public function test_service_provider_is_loaded()
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(\Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider::class),
            'Service provider should be loaded.'
        );
    }

    public function test_turkiye_migrate_command_runs_without_error()
    {
        $exitCode = $this->artisan('turkiye:migrate');
        $this->assertSame(0, $exitCode, 'turkiye:migrate command should exit with code 0');
    }

    public function test_models_exist_and_are_loaded()
    {
        // Test that the models exist and can be instantiated
        $this->artisan('turkiye:migrate');
        
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
        $this->artisan('turkiye:migrate');
        
        // Create test data directly
        $city = City::create(['id' => 1, 'name' => 'Test City']);
        $district = District::create(['id' => 1, 'city_id' => 1, 'name' => 'Test District']);
        $neighborhood = Neighborhood::create([
            'id' => 1, 
            'district_id' => 1, 
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
