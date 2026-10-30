<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Enums\NeighborhoodType;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

class NeighborhoodModelTest extends TestCase
{
    private City $city;
    private District $district;
    private Neighborhood $mahalle;
    private Neighborhood $koy;
    private Neighborhood $mezra;

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

        $this->city = City::create(['name' => 'ADIYAMAN', 'slug' => 'adiyaman']);
        $this->district = District::create(['city_id' => $this->city->id, 'name' => 'BESNİ', 'slug' => 'besni']);

        $attributes = ['city_id' => $this->city->id, 'district_id' => $this->district->id];
        $this->mahalle = Neighborhood::create($attributes + ['name' => 'ATATÜRK MAHALLESİ', 'slug' => 'ataturk-mahallesi']);
        $this->koy = Neighborhood::create($attributes + ['name' => 'AKDURAK KÖYÜ', 'slug' => 'akdurak-koyu', 'type' => NeighborhoodType::Koy]);
        $this->mezra = Neighborhood::create($attributes + [
            'name' => 'AKDURAK KÖYÜ, ÇAKMAK MEZRASI',
            'slug' => 'akdurak-koyu-cakmak-mezrasi',
            'type' => NeighborhoodType::Bagli,
            'parent_id' => $this->koy->id,
        ]);
    }

    public function test_type_defaults_to_mahalle_and_is_cast_to_enum(): void
    {
        $this->assertSame(NeighborhoodType::Mahalle, $this->mahalle->type);
        $this->assertSame(NeighborhoodType::Mahalle, $this->mahalle->fresh()->type);
        $this->assertSame(NeighborhoodType::Koy, $this->koy->fresh()->type);
        $this->assertSame('bagli', $this->mezra->fresh()->getRawOriginal('type'));
    }

    public function test_type_scopes(): void
    {
        $this->assertSame([$this->mahalle->id], Neighborhood::mahalle()->pluck('id')->all());
        $this->assertSame([$this->koy->id], Neighborhood::koy()->pluck('id')->all());
        $this->assertSame([$this->mezra->id], Neighborhood::bagli()->pluck('id')->all());
        $this->assertSame([$this->koy->id], Neighborhood::ofType(NeighborhoodType::Koy)->pluck('id')->all());
    }

    public function test_parent_and_children(): void
    {
        $this->assertTrue($this->mezra->parent->is($this->koy));
        $this->assertSame([$this->mezra->id], $this->koy->children->pluck('id')->all());
        $this->assertNull($this->koy->parent);
        $this->assertTrue($this->mahalle->children->isEmpty());
    }

    public function test_villages_on_district_and_city(): void
    {
        $this->assertSame([$this->koy->id], $this->district->villages->pluck('id')->all());
        $this->assertSame([$this->koy->id], $this->city->villages->pluck('id')->all());
        $this->assertSame(3, $this->district->neighborhoods()->count());
    }

    public function test_deleting_a_koy_detaches_its_bagli(): void
    {
        // Testbench's in-memory SQLite does not enforce foreign keys by default.
        Schema::enableForeignKeyConstraints();

        $this->koy->delete();

        $this->assertNull($this->mezra->fresh()->parent_id);
    }

    public function test_active_scope_combines_with_type_scopes(): void
    {
        $this->koy->update(['is_active' => false]);

        $this->assertTrue($this->district->villages()->active()->get()->isEmpty());
        $this->assertCount(2, $this->district->neighborhoods()->active()->get());
    }
}
