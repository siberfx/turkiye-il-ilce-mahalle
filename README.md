# Türkiye Adresler Laravel Paketi

Bu paket, Türkiye'nin şehir, ilçe ve mahalle verilerini Laravel projelerine kolayca entegre etmenizi sağlar. SQL dump dosyaları, dinamik tablo isimleri, seeder, migration ve Eloquent modelleri ile birlikte gelir.

## Özellikler

- Türkiye'nin tüm şehir, ilçe ve mahalle verileri
- Esnek yapılandırma seçenekleri
- Performans için optimize edilmiş veri yükleme
- Kolay entegrasyon için hazır migration dosyaları
- Seçici yayınlama özelliği ile ihtiyacınız olan dosyaları yükleyin
- Laravel 11.x ve üzeri sürümlerle uyumlu

## Kurulum

1. **Paketi Composer ile yükleyin**:

```bash
composer require siberfx/turkiye-address
```

2. **Servis Sağlayıcısını Kaydedin** (Laravel 10 ve öncesi için gerekebilir):

```php
'providers' => [
    Siberfx\TurkiyePackage\TurkiyeAdreslerServiceProvider::class,
],
```

## Kullanım

### Tüm Varlıkları Yayınlama

Tüm yapılandırma, migration ve seeder dosyalarını tek komutla yayınlayın:

```bash
php artisan turkiye:publish
```

### Seçerek Yayınlama

Sadece ihtiyacınız olan dosyaları seçerek yayınlayabilirsiniz:

```bash
# Sadece config dosyasını yayınla
php artisan turkiye:publish --config

# Sadece migration dosyalarını yayınla
php artisan turkiye:publish --migrations

# Sadece seeder ve SQL dosyalarını yayınla
php artisan turkiye:publish --seeders

# Mevcut dosyaların üzerine yaz
php artisan turkiye:publish --force
```

### Migration'ları Çalıştırma

Yayınlanan migration dosyalarını çalıştırın:

```bash
php artisan migrate
```

### Verileri Yükleme

Seeder'ı `DatabaseSeeder.php` dosyanıza ekleyin:

```php
$this->call(\Siberfx\TurkiyePackage\Database\Seeders\TurkiyeSeeder::class);
```

Ardından verileri yükleyin:

```bash
php artisan db:seed
```

## Yapılandırma

`config/turkiye-package.php` dosyasından tablo isimlerini ve model sınıflarını özelleştirebilirsiniz:

```php
return [
    'cities_table' => env('TURKIYE_CITIES_TABLE', 'cities'),
    'districts_table' => env('TURKIYE_DISTRICTS_TABLE', 'districts'),
    'neighborhoods_table' => env('TURKIYE_NEIGHBORHOODS_TABLE', 'neighborhoods'),
    'city_model' => 'Siberfx\\TurkiyePackage\\Models\\City',
    'district_model' => 'Siberfx\\TurkiyePackage\\Models\\District',
    'neighborhood_model' => 'Siberfx\\TurkiyePackage\\Models\\Neighborhood',
];
```

## Modellerin Kullanımı

```php
use Siberfx\TurkiyePackage\Models\City;
use Siberfx\TurkiyePackage\Models\District;
use Siberfx\TurkiyePackage\Models\Neighborhood;

// Tüm şehirleri getir
$iller = City::all();

// Belirli bir ile ait ilçeleri getir (city_id farklı tablo isimleri kullanılıyorsa değiştirilmnelidir )
$ilceler = District::where('city_id', 1)->get();

// Belirli bir ilçeye ait mahalleleri getir (district_id farklı tablo isimleri kullanılıyorsa değiştirilmnelidir )
$mahalleler = Neighborhood::where('district_id', 10)->get();
```

## İlişkiler

Modeller arasında tanımlanmış ilişkileri kullanabilirsiniz:

```php
// Bir şehrin tüm ilçeleri
$il = City::find(1);
$ilceler = $il->districts;

// Bir ilçenin tüm mahalleleri
$ilce = District::find(10);
$mahalleler = $ilce->neighborhoods;
```

## Sürüm Geçmişi

Detaylı değişiklikler için [CHANGELOG.md](CHANGELOG.md) dosyasına bakınız.

## Katkıda Bulunma

Katkılarınızı bekliyoruz! Lütfen önce bir konu açarak yapmak istediğiniz değişikliği tartışın.

## Lisans

Bu paket [MIT lisansı](LICENSE) altında lisanslanmıştır.

## İletişim

SiberFX - [info@siberfx.com](mailto:info@siberfx.com)

Proje Linki: [https://github.com/siberfx/turkiye-address](https://github.com/siberfx/turkiye-address)
