<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

class CityOrderingTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TurkiyeAddressServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../src/Database/migrations');
    }

    public function test_default_featured_cities_are_the_three_most_populous(): void
    {
        $this->assertSame([34, 6, 35], config('turkiye-package.featured_cities'));
    }

    public function test_seeded_cities_are_ordered_featured_first_then_turkish_alphabetical(): void
    {
        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $names = City::ordered()->pluck('name')->all();

        $this->assertCount(81, $names);
        $this->assertSame(['İSTANBUL', 'ANKARA', 'İZMİR', 'ADANA', 'ADIYAMAN'], array_slice($names, 0, 5));

        // Turkish letters sort after their Latin base letter
        $this->assertLessThan(array_search('ÇANAKKALE', $names), array_search('BURSA', $names));
        $this->assertLessThan(array_search('DENİZLİ', $names), array_search('ÇORUM', $names));
        $this->assertLessThan(array_search('ISPARTA', $names), array_search('IĞDIR', $names));
        $this->assertLessThan(array_search('ŞANLIURFA', $names), array_search('SİVAS', $names));
        $this->assertLessThan(array_search('TEKİRDAĞ', $names), array_search('ŞIRNAK', $names));
        $this->assertSame('ZONGULDAK', end($names));

        $this->assertSame(range(1, 81), DB::table('cities')->orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_sort_cities_command_uses_arguments_or_config(): void
    {
        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $this->artisan('turkiye:sort-cities', ['cities' => ['34', '35', '16', '6']])->assertExitCode(0);
        $this->assertSame(['İSTANBUL', 'İZMİR', 'BURSA', 'ANKARA', 'ADANA'], City::ordered()->limit(5)->pluck('name')->all());

        config(['turkiye-package.featured_cities' => [7]]);
        $this->artisan('turkiye:sort-cities')->assertExitCode(0);
        $this->assertSame(['ANTALYA', 'ADANA'], City::ordered()->limit(2)->pluck('name')->all());
    }

    public function test_featured_cities_can_be_set_from_the_environment(): void
    {
        putenv('TURKIYE_FEATURED_CITIES=16, 34');
        $config = require __DIR__ . '/../../config/turkiye-package.php';
        putenv('TURKIYE_FEATURED_CITIES');

        $this->assertSame([16, 34], $config['featured_cities']);
    }

    public function test_migration_orders_cities_of_an_existing_installation(): void
    {
        // Simulate an installation from before sort_order existed.
        $this->artisan('migrate:rollback', ['--step' => 1])->assertExitCode(0);
        $this->assertFalse(Schema::hasColumn('cities', 'sort_order'));
        DB::table('cities')->insert([
            ['id' => 1, 'name' => 'ADANA', 'slug' => 'adana', 'is_active' => true],
            ['id' => 35, 'name' => 'İZMİR', 'slug' => 'izmir', 'is_active' => true],
            ['id' => 16, 'name' => 'BURSA', 'slug' => 'bursa', 'is_active' => true],
        ]);

        $this->artisan('migrate')->assertExitCode(0);

        $this->assertSame([35, 1, 16], City::ordered()->pluck('id')->all());
    }
}
