# Türkiye Adresler Laravel Paketi

Bu paket, Türkiye'nin şehir, ilçe ve mahalle verilerini Laravel projelerine kolayca entegre etmenizi sağlar. SQL dump dosyaları, dinamik tablo isimleri, seeder, migration ve Eloquent modelleri ile birlikte gelir.

## Kurulum

1. **Paketin Kurulumu**

Composer ile yükleyin:
```bash
composer require siberfx/turkiye-address
```

2. **Servis Sağlayıcısını Kaydedin** (Laravel 10 ve öncesi için gerekebilir)
```php
'providers' => [
    Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider::class,
],
```

3. **Config ve Seeder Dosyalarını Yayınlayın**
```bash
php artisan vendor:publish --provider="Siberfx\\TurkiyePackage\\TurkiyeAdreslerServiceProvider" --tag=config
php artisan vendor:publish --provider="Siberfx\\TurkiyePackage\\TurkiyeAdreslerServiceProvider" --tag=seeders
```

4. **Migration Dosyalarını Çalıştırın**
```bash
php artisan turkiye:migrate
```

5. **Seeder'ı Çalıştırın**
Seeder'ı `DatabaseSeeder.php` dosyanıza ekleyin:
```php
$this->call(\Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder::class);
```
Ardından:
```bash
php artisan db:seed
```

## Yapılandırma (Config)

`config/turkiye-package.php` dosyasından tablo isimlerini değiştirebilirsiniz:
```php
return [
    'cities_table' => env('TURKIYE_CITIES_TABLE', 'cities'),
    'districts_table' => env('TURKIYE_DISTRICTS_TABLE', 'districts'),
    'neighborhoods_table' => env('TURKIYE_NEIGHBORHOODS_TABLE', 'neighborhoods'),
];
```

## Modellerin Kullanımı

```php
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;

$iller = City::all();
$ilceler = District::where('city_id', 1)->get();
$mahalleler = Neighborhood::where('district_id', 10)->get();
```

## Konsol Komutu

Migration dosyalarını çalıştırmak için:
```bash
php artisan turkiye:migrate
```

## Notlar
- Seeder büyük veri için optimize edilmiştir (timeout/memory limit ayarlı, tekrar çalıştırılabilir).
- `INSERT IGNORE` ile kayıtlar tekrar eklenmez.
- SQL dump dosyaları `src/database/sql-dumps` klasöründedir.

## Katkı ve Lisans
MIT Lisansı ile dağıtılmaktadır. Katkılarınızı bekleriz!

---

**İletişim:** info@siberfx.com
