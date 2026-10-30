<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;
use Siberfx\TurkiyePackage\Enums\NeighborhoodType;
use Siberfx\TurkiyePackage\Models\Neighborhood;
use Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider;

class SeederTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TurkiyeAddressServiceProvider::class,
        ];
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

    public function test_seeder_imports_bundled_dumps_with_referential_integrity(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertDirectoryDoesNotExist(database_path('sql-dumps'));

        (new TurkiyeSeeder())->setContainer($this->app)->run();

        // Türkiye has 81 provinces and 973 districts.
        $this->assertSame(81, DB::table('cities')->count());
        $this->assertSame(973, DB::table('districts')->count());
        $this->assertSame(94933, DB::table('neighborhoods')->count());

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

    public function test_seeder_unescapes_quoted_names(): void
    {
        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $this->assertSame(
            "BOĞAZİÇİ KÖYÜ, TAN'IN KOMLARI MEVKİİ",
            DB::table('neighborhoods')->where('id', 29235)->value('name')
        );
    }

    public function test_seeder_imports_records_synced_from_official_list(): void
    {
        (new TurkiyeSeeder())->setContainer($this->app)->run();

        // Added from the e-İçişleri mahalle/köy lists; ids start at 1,000,000.
        $this->assertSame(1024, DB::table('neighborhoods')->whereBetween('id', [1_000_000, 1_001_024])->count());

        // Gölbaşı (Adıyaman), Nurdağı (Gaziantep), Derecik (Hakkari)
        $this->assertSame('6 ŞUBAT MAHALLESİ', DB::table('neighborhoods')->where('id', 1000312)->value('name'));
        $this->assertSame(1354, DB::table('neighborhoods')->where('id', 1000312)->value('district_id'));
        $this->assertSame(2, DB::table('neighborhoods')->where('id', 1000312)->value('city_id'));
        $this->assertSame('6 ŞUBAT MAHALLESİ', DB::table('neighborhoods')->where('id', 1000906)->value('name'));
        $this->assertSame('ULAŞAN KÖYÜ', DB::table('neighborhoods')->where('id', 1001022)->value('name'));
        $this->assertSame(30, DB::table('neighborhoods')->where('id', 1001022)->value('city_id'));
    }

    public function test_seeder_sets_types_and_links_bagli_to_their_koy(): void
    {
        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $types = DB::table('neighborhoods')->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $this->assertEquals(['bagli' => 43459, 'koy' => 18377, 'mahalle' => 33097], $types->sort()->all() + []);
        $this->assertSame(42997, DB::table('neighborhoods')->whereNotNull('parent_id')->count());

        // Existing köy row with existing and newly synced bağlı (Akdurak, Besni)
        $akdurak = Neighborhood::find(4308);
        $this->assertSame(NeighborhoodType::Koy, $akdurak->type);
        $this->assertNull($akdurak->parent_id);
        $children = $akdurak->children()->pluck('name')->all();
        $this->assertContains('AKDURAK KÖYÜ, ÇAKMAK MEZRASI', $children);
        $this->assertContains('AKDURAK KÖYÜ, DAĞDİBİ', $children);

        // Köy that previously existed only inside its bağlı names (Mutlu, Çelikhan)
        $mutlu = Neighborhood::where('district_id', 1246)->where('name', 'MUTLU KÖYÜ')->sole();
        $this->assertGreaterThanOrEqual(1_001_025, $mutlu->id);
        $this->assertSame($mutlu->id, Neighborhood::find(4411)->parent_id);
        $this->assertTrue($mutlu->district->villages->contains($mutlu));

        // Belde mahalles stay mahalle
        $this->assertSame(
            NeighborhoodType::Mahalle,
            Neighborhood::where('name', 'like', '% BELDESİ, % MAHALLESİ')->first()->type
        );

        // Bağlı always belong to a köy (or a former köy, now a mahalle) of the same district
        $foreign = DB::table('neighborhoods as b')
            ->join('neighborhoods as p', 'b.parent_id', '=', 'p.id')
            ->where(fn ($q) => $q->whereColumn('b.district_id', '!=', 'p.district_id')->orWhere('p.type', 'bagli'))
            ->count();
        $this->assertSame(0, $foreign);
        $this->assertSame(0, DB::table('neighborhoods')->whereColumn('parent_id', 'id')->count());

        // Two official köys one letter apart keep separate rows and their own bağlı (Gümüşhane Merkez)
        $tandirlik = Neighborhood::where('district_id', 1369)->where('name', 'TANDIRLIK KÖYÜ')->sole();
        $bandirlik = Neighborhood::where('district_id', 1369)->where('name', 'BANDIRLIK KÖYÜ')->sole();
        $this->assertContains('TANDIRLIK KÖYÜ, FERHATAĞA', $tandirlik->children()->pluck('name')->all());
        $this->assertNotContains('BANDIRLIK KÖYÜ, FERHATAĞA', $bandirlik->children()->pluck('name')->all());

        // A bağlı hangs under the köy, not under a same-named mahalle (Pınarbaşı, Besni)
        $this->assertSame(4370, Neighborhood::where('name', 'PINARBAŞI KÖYÜ, OĞULCUK')->sole()->parent_id);

        // A köy that became a mahalle keeps its bağlı under the mahalle (Osmaniye, Giresun Merkez)
        $this->assertSame(30717, Neighborhood::where('name', 'OSMANİYE MAHALLESİ, FATİH')->sole()->parent_id);
    }

    public function test_seeder_prefers_published_dumps(): void
    {
        File::ensureDirectoryExists(database_path('sql-dumps'));
        File::put(database_path('sql-dumps/cities.sql'), "INSERT INTO `cities` (`id`, `name`) VALUES\n('1','ADANA');");
        File::put(database_path('sql-dumps/districts.sql'), "INSERT INTO `districts` (`id`, `city_id`, `name`) VALUES\n('1104','1','SEYHAN');");
        File::put(database_path('sql-dumps/neighborhoods.sql'), "INSERT INTO `neighborhoods` (`id`, `district_id`, `name`) VALUES\n('1','1104','AKKAPI MAHALLESİ'),\n('2','9999','ORPHAN MAHALLESİ');");

        (new TurkiyeSeeder())->setContainer($this->app)->run();

        $this->assertSame(1, DB::table('cities')->count());
        $this->assertSame(1, DB::table('districts')->count());
        // Neighborhoods of unknown districts are skipped.
        $this->assertSame(1, DB::table('neighborhoods')->count());
        $this->assertSame(1, DB::table('neighborhoods')->where('id', 1)->value('city_id'));
    }
}
