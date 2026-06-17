# Değişiklik Kaydı

Bu dosya, pakette yapılan tüm önemli değişiklikleri dokümante eder.

## [2.0.0] - 2026-06-17

### Değişiklikler (Breaking)

- Sadece Laravel 12 ve 13 desteklenir (`illuminate/support: ^12.0|^13.0`)
- Sadece PHP 8.4 desteklenir (`php: ^8.4`)
- `orchestra/testbench` `^10.0|^11.0`, `phpunit/phpunit` `^11.5|^12.0` olarak güncellendi
- Tüm yapılandırma anahtarı `turkiye-package` olarak standartlaştırıldı (önceden
  servis sağlayıcı, seeder ve migration'lar `turkiye-adresler`/`turkiye-package`
  anahtarlarını karışık kullanıyordu; modeller her zaman varsayılana düşüyordu)

### Düzeltmeler

- Servis sağlayıcıdaki yanlış yayınlama yolları düzeltildi (`database/seeders` →
  `Database/Seeders`, `database/sql-dumps` → `Database/sql-dumps`); büyük/küçük
  harfe duyarlı sistemlerde (Linux/CI) çalışmıyordu
- Migration'lar artık `loadMigrationsFrom` ile otomatik yükleniyor ve `migrations`
  etiketiyle yayınlanabiliyor
- Seeder artık sürücü-bağımsız (MySQL/MariaDB, SQLite, PostgreSQL); SQLite'ta
  `SET FOREIGN_KEY_CHECKS` hatası giderildi
- Seeder ilişki sütunları yapılandırmadan okunuyor; `districts` tablosundaki
  yanlış `district_id` yerine doğru `city_id` yazılıyor
- Migration'larda olmayan `created_at`/`updated_at` sütunlarına yazma kaldırıldı
- Stub'daki `Siberfox` yazım hatası `Siberfx` olarak düzeltildi
- GitHub Actions iş akışı standart testbench matrisiyle yeniden yazıldı; yanlış
  paket adı (`siberfx/turkiye-address`) düzeltildi
- `composer.json` `autoload-dev` (Tests\\) eklendi; `authors.website` geçersiz
  anahtarı `homepage` olarak düzeltildi
- Test paketi gerçek paket migration'larına karşı çalışacak şekilde düzeltildi;
  seeder bütünlük testi eklendi

### Eklendi

- Tüm sınıflara `declare(strict_types=1)` ve dönüş tipleri eklendi

## [1.1.0] - 2025-07-18

### Eklendi

- Yeni `turkiye:publish` komutu eklendi
- Seçici yayınlama özelliği eklendi (sadece ihtiyaç duyulan dosyaları yükleme)
- Migration dosyaları eklendi (şehirler, ilçeler ve mahalleler için)
- Gelişmiş veri yükleme mekanizması eklendi
- Performans iyileştirmeleri yapıldı

### Değişiklikler

- `turkiye:publish-assets` komutu `turkiye:publish` olarak değiştirildi
- Yapılandırma dosyasına model sınıfları eklendi
- Veritabanı şeması iyileştirildi

### Düzeltmeler

- Seeder'da performans iyileştirmeleri yapıldı
- Veri bütünlüğü için kontroller eklendi
- Hata mesajları iyileştirildi

## [1.0.0] - 2025-07-15

### Eklendi

- İlk kararlı sürüm yayınlandı
- Temel şehir, ilçe ve mahalle veri yapısı eklendi
- Temel seeder ve migration dosyaları eklendi
