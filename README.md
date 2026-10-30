# Türkiye Addresses for Laravel

A Laravel package that ships the complete administrative address hierarchy of Türkiye — **81 cities (il)**, **973 districts (ilçe)** and **75,422 neighborhoods (mahalle, köy, mevkii, mezra)** — together with migrations, a fast seeder and Eloquent models with relationships.

- Works out of the box: migrations auto-load and the seeder reads the bundled SQL dumps, so nothing has to be published.
- Database-agnostic seeder (MySQL / MariaDB, PostgreSQL, SQLite), chunked inserts inside a single transaction.
- Configurable table names, foreign key column names and model classes.
- Laravel **12** and **13**, PHP **8.3+**.

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Database schema](#database-schema)
- [Usage](#usage)
  - [Querying](#querying)
  - [Relationships](#relationships)
  - [Active scope](#active-scope)
  - [Validation](#validation)
  - [Dependent dropdowns (city → district → neighborhood)](#dependent-dropdowns-city--district--neighborhood)
  - [Attaching addresses to your own models](#attaching-addresses-to-your-own-models)
  - [Caching](#caching)
- [Artisan commands](#artisan-commands)
- [Configuration](#configuration)
- [Customising](#customising)
- [About the data](#about-the-data)
- [Testing](#testing)
- [Changelog](#changelog)
- [License](#license)

## Requirements

| Package version | Laravel   | PHP       |
| --------------- | --------- | --------- |
| 2.x             | 12, 13    | 8.3 – 8.5 |

## Installation

```bash
composer require siberfx/turkiye-address
```

The service provider (`Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider`) is registered automatically through package discovery.

## Quick start

```bash
# Create the cities, districts and neighborhoods tables and import the data
php artisan turkiye:migrate --seed
```

Or use the standard Laravel workflow — the package migrations are loaded automatically:

```bash
php artisan migrate
php artisan db:seed --class="Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder"
```

To seed together with the rest of your application, call it from `database/seeders/DatabaseSeeder.php`:

```php
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder;

public function run(): void
{
    $this->call(TurkiyeSeeder::class);
}
```

> The seeder **truncates** the three tables before importing, so it is safe to re-run and always leaves you with a clean copy of the data.

## Database schema

```
cities                  districts                 neighborhoods
──────────              ──────────────            ──────────────────
id          PK          id          PK            id           PK
name                    city_id     FK → cities   city_id      FK → cities
slug        unique      name                      district_id  FK → districts
is_active               slug                      name
                        is_active                 slug
                                                  is_active
```

- `cities.id` is the official plate code (1 = Adana, 6 = Ankara, 34 = İstanbul, …).
- `neighborhoods.city_id` is denormalised so you can filter neighborhoods by city without a join.
- Foreign keys cascade on delete. Tables have no timestamps.
- Names are stored in upper case as published officially (e.g. `İSTANBUL`, `KADIKÖY`); slugs are ASCII (`istanbul`, `kadikoy`).

## Usage

### Querying

```php
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;

// All cities, alphabetically
$cities = City::orderBy('name')->get();

// A city by plate code or slug
$istanbul = City::find(34);
$izmir    = City::where('slug', 'izmir')->first();

// Districts of a city
$districts = District::where('city_id', 6)->orderBy('name')->get();

// Neighborhoods of a district
$neighborhoods = Neighborhood::where('district_id', 1103)->get();

// Search neighborhoods by name inside a city
$results = Neighborhood::where('city_id', 34)
    ->where('name', 'like', '%BAĞDAT%')
    ->limit(20)
    ->get();

// Key/value list for a <select>
$options = City::orderBy('name')->pluck('name', 'id');
```

### Relationships

| Model          | Relationship       | Type        |
| -------------- | ------------------ | ----------- |
| `City`         | `districts()`      | `HasMany`   |
| `City`         | `neighborhoods()`  | `HasMany`   |
| `District`     | `city()`           | `BelongsTo` |
| `District`     | `neighborhoods()`  | `HasMany`   |
| `Neighborhood` | `district()`       | `BelongsTo` |
| `Neighborhood` | `city()`           | `BelongsTo` |

```php
$city = City::find(35);

$city->districts;                 // all districts of İzmir
$city->neighborhoods()->count();  // number of neighborhoods in İzmir

$district = District::with('city')->find(1103);
echo "{$district->name} / {$district->city->name}";   // ADALAR / İSTANBUL

// Full address line for a neighborhood
$neighborhood = Neighborhood::with(['district', 'city'])->find(1);
echo implode(', ', [
    $neighborhood->name,
    $neighborhood->district->name,
    $neighborhood->city->name,
]); // AHMET REMZİ YÜREĞİR MAHALLESİ, SEYHAN, ADANA

// Eager-load counts
$cities = City::withCount(['districts', 'neighborhoods'])->get();

// Cities with more than 20 districts
$big = City::has('districts', '>', 20)->get();
```

### Active scope

Every row has an `is_active` flag (cast to `bool`). Deactivate records you don't want to offer and filter with the `active()` scope:

```php
District::where('city_id', 34)->update(['is_active' => false]);
District::whereIn('id', [1103, 1166])->update(['is_active' => true]);

$offered = District::active()->where('city_id', 34)->get();

$city->districts()->active()->get();
```

### Validation

```php
use Illuminate\Validation\Rule;

$request->validate([
    'city_id' => ['required', 'integer', 'exists:cities,id'],

    'district_id' => [
        'required', 'integer',
        Rule::exists('districts', 'id')->where('city_id', $request->integer('city_id')),
    ],

    'neighborhood_id' => [
        'required', 'integer',
        Rule::exists('neighborhoods', 'id')->where('district_id', $request->integer('district_id')),
    ],
]);
```

The scoped `Rule::exists` checks make sure the district belongs to the selected city and the neighborhood to the selected district.

### Dependent dropdowns (city → district → neighborhood)

**Routes / controller:**

```php
use Illuminate\Support\Facades\Route;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;

Route::get('/api/cities', fn () =>
    City::active()->orderBy('name')->get(['id', 'name'])
);

Route::get('/api/cities/{city}/districts', fn (City $city) =>
    $city->districts()->active()->orderBy('name')->get(['id', 'name'])
);

Route::get('/api/districts/{district}/neighborhoods', fn (District $district) =>
    $district->neighborhoods()->active()->orderBy('name')->get(['id', 'name'])
);
```

**Front end (vanilla JS):**

```html
<select id="city"></select>
<select id="district" disabled></select>
<select id="neighborhood" disabled></select>

<script>
const fill = async (select, url) => {
    const items = await (await fetch(url)).json();
    select.innerHTML = '<option value="">Select…</option>' +
        items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
    select.disabled = false;
};

const city = document.getElementById('city');
const district = document.getElementById('district');
const neighborhood = document.getElementById('neighborhood');

fill(city, '/api/cities');
city.addEventListener('change', () => {
    neighborhood.disabled = true;
    fill(district, `/api/cities/${city.value}/districts`);
});
district.addEventListener('change', () =>
    fill(neighborhood, `/api/districts/${district.value}/neighborhoods`)
);
</script>
```

**Livewire 3 component:**

```php
use Livewire\Component;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;

class AddressPicker extends Component
{
    public ?int $cityId = null;
    public ?int $districtId = null;
    public ?int $neighborhoodId = null;

    public function updatedCityId(): void
    {
        $this->reset('districtId', 'neighborhoodId');
    }

    public function updatedDistrictId(): void
    {
        $this->reset('neighborhoodId');
    }

    public function render()
    {
        return view('livewire.address-picker', [
            'cities' => City::orderBy('name')->pluck('name', 'id'),
            'districts' => $this->cityId
                ? District::where('city_id', $this->cityId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'neighborhoods' => $this->districtId
                ? Neighborhood::where('district_id', $this->districtId)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }
}
```

### Attaching addresses to your own models

```php
// Migration
Schema::create('addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('city_id')->constrained('cities');
    $table->foreignId('district_id')->constrained('districts');
    $table->foreignId('neighborhood_id')->nullable()->constrained('neighborhoods');
    $table->string('line');
    $table->string('postal_code', 5)->nullable();
    $table->timestamps();
});
```

```php
// app/Models/Address.php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;

class Address extends Model
{
    protected $fillable = ['city_id', 'district_id', 'neighborhood_id', 'line', 'postal_code'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->line,
            $this->neighborhood?->name,
            $this->district->name . ' / ' . $this->city->name,
            $this->postal_code,
        ])->filter()->implode(', ');
    }
}
```

### Caching

The address data rarely changes, so it is a good candidate for caching:

```php
use Illuminate\Support\Facades\Cache;

$cities = Cache::rememberForever('turkiye.cities', fn () =>
    City::orderBy('name')->get(['id', 'name', 'slug'])
);

$districts = Cache::rememberForever("turkiye.cities.{$cityId}.districts", fn () =>
    District::where('city_id', $cityId)->orderBy('name')->get(['id', 'name'])
);
```

Clear these keys after re-seeding or toggling `is_active`.

## Artisan commands

### `turkiye:migrate`

Runs only the package migrations.

```bash
php artisan turkiye:migrate            # run the package migrations
php artisan turkiye:migrate --seed     # ...and import the data
php artisan turkiye:migrate --force    # allow running in production
php artisan turkiye:migrate --fresh    # ⚠ drops ALL tables of the database first (migrate:fresh)
```

### `turkiye:publish`

Publishes package files into your application. With no option, everything is published.

```bash
php artisan turkiye:publish                # config + migrations + seeder & SQL dumps
php artisan turkiye:publish --config       # config/turkiye-package.php
php artisan turkiye:publish --migrations   # database/migrations/*
php artisan turkiye:publish --seeders      # database/seeders/TurkiyeSeeder.php + database/sql-dumps/*.sql
php artisan turkiye:publish --force        # overwrite existing files
```

The same files can be published with `vendor:publish`:

```bash
php artisan vendor:publish --provider="Siberfx\TurkiyePackage\TurkiyeAddressServiceProvider" --tag=config
```

Available tags: `config`, `migrations`, `seeders`.

## Configuration

Publish the config file if you need to change the defaults:

```bash
php artisan turkiye:publish --config
```

```php
// config/turkiye-package.php
return [
    // Table names
    'cities_table' => env('TURKIYE_CITIES_TABLE', 'cities'),
    'districts_table' => env('TURKIYE_DISTRICTS_TABLE', 'districts'),
    'neighborhoods_table' => env('TURKIYE_NEIGHBORHOODS_TABLE', 'neighborhoods'),

    // Foreign key column names
    'cities_relation_id' => env('TURKIYE_CITIES_RELATION_ID', 'city_id'),
    'districts_relation_id' => env('TURKIYE_DISTRICTS_RELATION_ID', 'district_id'),
    'neighborhoods_relation_id' => env('TURKIYE_NEIGHBORHOODS_RELATION_ID', 'neighborhood_id'),

    // Model classes used by the relationships
    'city_model' => Siberfx\TurkiyePackage\Models\City::class,
    'district_model' => Siberfx\TurkiyePackage\Models\District::class,
    'neighborhood_model' => Siberfx\TurkiyePackage\Models\Neighborhood::class,
];
```

Example — avoid clashing with an existing `cities` table by prefixing all tables via `.env`:

```dotenv
TURKIYE_CITIES_TABLE=tr_cities
TURKIYE_DISTRICTS_TABLE=tr_districts
TURKIYE_NEIGHBORHOODS_TABLE=tr_neighborhoods
```

Migrations, seeder and models all read these values. Set them **before** running the migrations.

## Customising

### Your own models

Extend the package models to add attributes, scopes or relationships, then point the config at them so the package relationships return your classes:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Siberfx\TurkiyePackage\Models\City as BaseCity;

class City extends BaseCity
{
    // "İSTANBUL" -> "İstanbul"
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => mb_convert_case(
            mb_strtolower($this->name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'
        ));
    }
}
```

```php
// config/turkiye-package.php
'city_model' => App\Models\City::class,
```

> Tip: `mb_strtolower` does not apply Turkish casing rules for `I`/`İ`. If exact Turkish casing matters, use `Str::lower()` after replacing `I` → `ı` and `İ` → `i`.

### Your own seeder or data

`php artisan turkiye:publish --seeders` creates:

- `database/seeders/TurkiyeSeeder.php` — a class extending the package seeder, where you can override any step (`importCities()`, `importDistricts()`, `importNeighborhoods()`, `dumpPath()`, …).
- `database/sql-dumps/*.sql` — editable copies of the data. When these files exist the seeder uses them instead of the bundled dumps.

```php
namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder as BaseTurkiyeSeeder;

class TurkiyeSeeder extends BaseTurkiyeSeeder
{
    public function run(): void
    {
        parent::run();

        // Only offer neighborhoods proper (skip köy / mevkii / mezra entries)
        DB::table($this->neighborhoodsTable)
            ->where('name', 'not like', '%MAHALLESİ')
            ->update(['is_active' => false]);
    }
}
```

```bash
php artisan db:seed --class="Database\Seeders\TurkiyeSeeder"
```

## About the data

| Level         | Rows   | Notes                                                                 |
| ------------- | ------ | --------------------------------------------------------------------- |
| Cities        | 81     | IDs are the official plate codes                                      |
| Districts     | 973    | Every province's district count matches the official administrative list |
| Neighborhoods | 75,422 | ~33k `MAHALLESİ`, ~23k `MEVKİİ`, ~12.6k `KÖYÜ`, ~5k `MEZRASI`, ~1.4k `EVLERİ` |

The neighborhood level follows the postal (PTT) address register, so besides mahalle it also contains villages (köy), localities (mevkii) and hamlets (mezra). Street (sokak) level data is not included.

### Source and official reference

Mahalle and köy records are checked against the official inventory of administrative divisions published by the Ministry of Interior (İçişleri Bakanlığı, İller İdaresi Genel Müdürlüğü):

**[Türkiye Mülki İdare Bölümleri Envanteri — e-icisleri.gov.tr](https://www.e-icisleri.gov.tr/Anasayfa/MulkiIdariBolumleri.aspx)**

| Official list (October 2026) | Official count | Present in this package |
| ---------------------------- | -------------- | ----------------------- |
| İl (province)                | 81             | 81                      |
| Mahalle (neighborhood)       | 32,327         | all                     |
| Köy (village)                | 18,210         | all                     |

- Records that were missing from the original dataset were added with IDs starting at **1,000,000**, so existing IDs — and any foreign keys pointing at them in your application — never change.
- About 940 official names differ from the dataset by a single letter (e.g. `KEKLİKÇE` / `KEKLİKÇİ`); these are the same places and were kept under their existing IDs and spelling.
- Records are never deleted or renumbered by a data update. If a place moves to another district, the new location is added and the old row is kept.

Found a missing or outdated record? Please open an [issue](https://github.com/siberfx/turkiye-il-ilce-mahalle/issues) with the official source.

## Testing

```bash
composer install
composer test
```

The test suite runs against an in-memory SQLite database and checks the full import, including referential integrity between cities, districts and neighborhoods.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for what has changed between versions.

## Contributing

Contributions are welcome! Please open an issue first to discuss the change you would like to make.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Contact

SiberFX — [info@siberfx.com](mailto:info@siberfx.com)

Project: [https://github.com/siberfx/turkiye-il-ilce-mahalle](https://github.com/siberfx/turkiye-il-ilce-mahalle)
