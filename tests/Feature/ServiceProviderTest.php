<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider;

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

    public function test_city_model_returns_data()
    {
        // Run migration and seeder to ensure data exists
        $this->artisan('turkiye:migrate');
        $this->artisan('db:seed', ['--class' => 'Siberfx\\TurkiyePackage\\Database\\Seeders\\TurkiyeSeeder']);

        $city = \Siberfx\TurkiyePackage\Models\City::query()->first();
        $this->assertNotNull($city, 'City::first() should return a record after seeding.');
        $this->assertNotEmpty($city->name ?? '', 'City record should have a name.');
    }

    public function test_district_model_returns_data()
    {
        $this->artisan('turkiye:migrate');
        $this->artisan('db:seed', ['--class' => 'Siberfx\\TurkiyePackage\\Database\\Seeders\\TurkiyeSeeder']);

        $district = \Siberfx\TurkiyePackage\Models\District::query()->first();
        $this->assertNotNull($district, 'District::first() should return a record after seeding.');
        $this->assertNotEmpty($district->name ?? '', 'District record should have a name.');
    }

    public function test_neighborhood_model_returns_data()
    {
        $this->artisan('turkiye:migrate');
        $this->artisan('db:seed', ['--class' => 'Siberfx\\TurkiyePackage\\Database\\Seeders\\TurkiyeSeeder']);

        $neighborhood = \Siberfx\TurkiyePackage\Models\Neighborhood::query()->first();
        $this->assertNotNull($neighborhood, 'Neighborhood::first() should return a record after seeding.');
        $this->assertNotEmpty($neighborhood->name ?? '', 'Neighborhood record should have a name.');
    }
}
