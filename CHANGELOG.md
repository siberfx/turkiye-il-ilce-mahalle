# Changelog

All notable changes to this package are documented in this file.

## [Unreleased]

### Added

- `cities.sort_order` column (new additive migration `2026_10_30_000005_add_sort_order_to_cities_table`)
  and `City::ordered()`: the configured big cities first, then every other city in Turkish
  alphabetical order (independent of the database collation)
- `featured_cities` config option / `TURKIYE_FEATURED_CITIES` env (plate codes); default
  İstanbul (34), Ankara (6), İzmir (35), the three most populous provinces
- `php artisan turkiye:sort-cities [codes...]` to recalculate the order; the seeder and the
  migration apply it automatically
- `src/Database/sql-dumps/inactive.json`: records the seeder keeps but marks `is_active = false`
  (28 rows: places renamed or moved to another district in the official inventory — e.g. Şişli
  `MERKEZ` → `ŞİŞLİ MERKEZ`, Balıbıdık köyü Yapraklı → Çankırı Merkez — and the bağlı of moved köys)
- Tests: `CityOrderingTest`, `CityOrderTest`, official spelling / inactive records (47 tests)

### Changed

- Names now use the official spelling: 2,065 records that differed by a single letter were
  renamed in place (590 mahalle, 335 köy, 1,140 bağlı; e.g. `KEKLİKÇİ` → `KEKLİKÇE`,
  `GÖNAN KÖYÜ` → `GÖNEN KÖYÜ`). IDs are unchanged; bağlı of a renamed köy are renamed with it and
  stay linked. Their `slug` changes accordingly
- Re-seeding deactivates the rows listed in `inactive.json` on existing installations too

### Fixed

- 10 mahalles added in 2.0.1 carried their own district as a fake belde prefix
  (e.g. `DÜZİÇİ BELDESİ, HACILAR MAHALLESİ` → `HACILAR MAHALLESİ`)

## [2.2.1] - 2026-10-30

### Fixed

- **Re-seeding no longer deletes application data.** The seeder used to `TRUNCATE` the cities,
  districts and neighborhoods tables. On PostgreSQL Laravel truncates with `CASCADE`, which also
  emptied every application table with a foreign key to them (e.g. an `addresses` table). The
  seeder now upserts by ID and never truncates or deletes: new records are added and existing
  ones refreshed in place. **If you are on PostgreSQL, upgrade to 2.2.1 before re-seeding for
  2.2.0.**
- `is_active` is only set for new rows, so records you deactivated stay deactivated after a re-seed
- The seeder no longer disables foreign key checks. On PostgreSQL that required
  `SET session_replication_role`, which needs superuser rights and failed on managed databases
  (RDS, Supabase, Neon, …). Parents are now always written before their children instead
- PostgreSQL ID sequences are moved past the seeded IDs, so the application can keep creating rows
- Districts of unknown cities are skipped, like neighborhoods of unknown districts already were

### Changed

- Removed the unused `neighborhoods_relation_id` config option
- Removed the seeder's `disableForeignKeyChecks()` / `enableForeignKeyChecks()` and
  `insertInChunks()` methods (replaced by `upsertInChunks()`); only relevant if you extended the seeder
- More Packagist keywords (`turkiye`, `il`, `ilce`, `mahalle`, `koy`, `bagli`, …)

### Added

- CI jobs on MySQL 8.4 and PostgreSQL 17 for Laravel 12 and 13
- `ReseedTest` and an idempotency test for the full import (37 tests)

## [2.2.0] - 2026-10-30

### Added

- `type` (`mahalle`, `koy`, `bagli`) and `parent_id` columns on `neighborhoods`, in a new
  additive migration (`2026_10_30_000004_add_type_and_parent_to_neighborhoods_table`);
  run `php artisan migrate` and re-seed to fill them
- `NeighborhoodType` enum (cast on `Neighborhood::$type`)
- `Neighborhood::parent()` / `children()`, `City::villages()` / `District::villages()`,
  and `mahalle()`, `koy()`, `bagli()`, `ofType()` query scopes
- Bağlı (mevkii, mezra, …) synced from the official e-İçişleri bağlı list: 13,763 bağlı added,
  and 5,748 köy that previously existed only inside their bağlı names got their own row.
  Every official il, ilçe, mahalle, köy and bağlı is now present. Neighborhoods: 75,422 → 94,933
  (33,097 mahalle, 18,377 köy, 43,459 bağlı); 42,997 bağlı are linked to their köy
- New records use IDs 1,001,025–1,020,535; no existing ID or foreign key was changed
- Badges and a test overview in the README
- Tests: `NeighborhoodTypeTest`, `NeighborhoodModelTest`, `CustomConfigurationTest`, and seeder
  tests for types and parent links (32 tests)

## [2.1.0] - 2026-10-30

### Changed

- The Composer package was renamed from `siberfx/turkiye-adresler-json` to
  `siberfx/turkiye-address`. Install it with `composer require siberfx/turkiye-address`;
  existing installs should replace the old name in their `composer.json`
- README install instructions updated to the new package name

## [2.0.1] - 2026-10-30

### Added

- 1,024 mahalle and köy records synced from the Ministry of Interior's official
  inventory ([e-icisleri.gov.tr](https://www.e-icisleri.gov.tr/Anasayfa/MulkiIdariBolumleri.aspx)):
  758 mahalle (107 of them inside a belde) and 266 köy. Neighborhoods: 74,398 → 75,422.
  Every official mahalle (32,327) and köy (18,210) is now present
- New records use IDs from 1,000,000 upwards; no existing ID or foreign key was changed
- README: "Source and official reference" section

## [2.0.0] - 2026-10-30

### Changed (Breaking)

- Only Laravel 12 and 13 are supported (`illuminate/support: ^12.0|^13.0`)
- PHP 8.3, 8.4 and 8.5 are supported (`php: ^8.3`)
- The service provider `TurkiyeAdreslerServiceProvider` was renamed to
  `TurkiyeAddressServiceProvider` (no action needed when registered via package discovery)
- Removed the unused model properties/methods and `resetAutoIncrement()` from the seeder
- The `seeders` publish tag now publishes a thin `Database\Seeders\TurkiyeSeeder` class
  extending the package seeder (previously a file with the package namespace was copied,
  which broke autoloading)
- `orchestra/testbench` updated to `^10.0|^11.0`, `phpunit/phpunit` to `^11.5|^12.0`
- All configuration access standardised on the `turkiye-package` key (previously the
  service provider, seeder and migrations mixed `turkiye-adresler` and `turkiye-package`,
  so the models always fell back to the defaults)

### Fixed

- Wrong publish paths in the service provider (`database/seeders` → `Database/Seeders`,
  `database/sql-dumps` → `Database/sql-dumps`); they failed on case-sensitive
  file systems (Linux/CI)
- Migrations are now loaded automatically via `loadMigrationsFrom` and can be published
  with the `migrations` tag
- The seeder is now driver-agnostic (MySQL/MariaDB, SQLite, PostgreSQL); fixed the
  `SET FOREIGN_KEY_CHECKS` error on SQLite
- The seeder reads relation columns from the config and writes the correct `city_id`
  (instead of `district_id`) to the `districts` table
- Removed writes to `created_at`/`updated_at` columns that don't exist in the migrations
- Fixed the `Siberfox` typo in the stub (`Siberfx`)
- Rewrote the GitHub Actions workflow with a standard testbench matrix; fixed the wrong
  package name (`siberfx/turkiye-address`)
- Added `autoload-dev` (`Tests\`) to `composer.json`; replaced the invalid
  `authors.website` key with `homepage`
- The test suite now runs against the real package migrations; added a seeder
  integrity test
- 2 neighborhood records containing an apostrophe (`TAN\'IN`, `KURS\'TAN`) were silently
  skipped by the seeder; the parser now handles escaped characters (all 74,398 records
  are imported)
- The seeder now works without publishing the SQL dumps (a published copy is used when present)
- `turkiye:migrate` runs only the package migrations instead of all of `database/migrations`;
  `--seed` calls the package seeder instead of a `TurkiyeSeeder` class that may not exist
- Foreign key checks are re-enabled even when seeding fails; the import runs in a single
  transaction
- Added the missing `LICENSE` file

### Added

- `declare(strict_types=1)` and return types on all classes
- `City::neighborhoods()` and `Neighborhood::city()` relationships
- `active()` scope and `is_active` boolean cast on all models
- Relationships use the `city_model` / `district_model` / `neighborhood_model` config
  options (now included in the config file)
- `turkiye:migrate --force` option
- Extended English README (usage examples, validation, dependent dropdowns, Livewire)
- CI matrix: PHP 8.3 / 8.4 / 8.5 × Laravel 12 / 13

## [1.1.0] - 2025-07-18

### Added

- New `turkiye:publish` command
- Selective publishing (publish only the files you need)
- Migration files for cities, districts and neighborhoods
- Improved data loading mechanism
- Performance improvements

### Changed

- The `turkiye:publish-assets` command was renamed to `turkiye:publish`
- Model classes added to the config file
- Improved database schema

### Fixed

- Performance improvements in the seeder
- Added data integrity checks
- Improved error messages

## [1.0.0] - 2025-07-15

### Added

- First stable release
- Basic city, district and neighborhood data structure
- Basic seeder and migration files
