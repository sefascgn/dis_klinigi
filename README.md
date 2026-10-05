# Diş Kliniği Yönetim Paneli

Laravel 12 + MySQL ile geliştirilmiş, diş kliniklerinin günlük operasyonlarını yönetmesi için web tabanlı panel.

**Arayüz:** Blade + Tailwind CSS + Alpine.js (Laravel Breeze altyapısı, Vite ile derlenir)

## Kurulum

```bash
composer install
npm install && npm run build
cp .env.example .env        # DB_* ve ADMIN_PASSWORD değerlerini doldurun
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

`db:seed` şunları oluşturur: klinik bilgileri kaydı, varsayılan işlem tanımları ve ilk yönetici
(`ADMIN_EMAIL` / `ADMIN_PASSWORD`). `APP_ENV=local` ise ayrıca demo verisi eklenir:

| Rol       | E-posta               | Şifre      |
|-----------|-----------------------|------------|
| Yönetici  | admin@klinik.test     | password   |
| Sekreter  | sekreter@klinik.test  | password   |
| Doktor    | doktor@klinik.test    | password   |

> Production ortamında güçlü bir `ADMIN_PASSWORD` kullanın; demo verisi bu ortamda eklenmez.

## Testler

Testler MySQL üzerinde `dis_klinigi_test` veritabanını kullanır (`phpunit.xml`):

```bash
mysql -uroot -e "CREATE DATABASE dis_klinigi_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan test
```

## Modüller

- **Gösterge paneli:** günlük / bekleyen / tamamlanan randevular, toplam hasta, günlük ve aylık gelir, bugünün programı, yaklaşan randevular
- **Hastalar:** CRUD, arama, detay kartı (iletişim, sağlık uyarısı, hasta geçmişi zaman çizelgesi, randevular, tedaviler, FDI diş şeması, ödemeler, belgeler, bakiye)
  - **Anamnez:** diyabet, kan sulandırıcı, hamilelik vb. kontrol listesi; kritik durumlar kırmızı uyarı olarak görünür
  - **Belgeler:** röntgen, fotoğraf, onam formu (JPG/PNG/WEBP/PDF, 10 MB); dosyalar herkese açık olmayan diskte saklanır ve yalnızca giriş yapmış kullanıcıya sunulur
  - **Hazır listeler:** "Borçlu hastalar" ve "Kontrolü gelenler" (son ziyaretten bu yana ayarlanabilir süre geçmiş, ileri randevusu olmayanlar)
- **Doktorlar:** CRUD, uzmanlık, panel hesabına bağlama, doktorun randevuları
- **Randevular:** gün / hafta / ay takvimi ve liste; durumlar (Bekliyor, Onaylandı, Tamamlandı, İptal edildi), doktor bazında çakışma kontrolü, iptal nedeni
- **WhatsApp hatırlatma:** randevu ve kontrol hatırlatmaları için hazır mesajlı `wa.me` bağlantıları (ek servis veya API anahtarı gerekmez)
- **Yazdırma / PDF:** tedavi planı ve fiyat teklifi, hasta hesap ekstresi, tahsilat makbuzu
- **Giderler (yönetici):** kira, maaş, laboratuvar, malzeme, fatura vb. kategorilerle gider kaydı; aylık sabit giderler tek tıkla "Tekrarla"
- **Raporlar (yönetici):** gelir–gider odaklı finans paneli
  - Net kâr, gelen para (tahsilat), giden para (gider) ve ciro kartları; önceki dönemle karşılaştırma (tam ay → önceki takvim ayı)
  - Son 12 ay gelen/giden sütun grafiği, aylık net kâr/zarar grafiği, aylık özet tablosu
  - Gider dağılımı, işlem ve doktor bazında ciro, tahsilat yöntemleri
  - Gösterge panelinde yönetici için bu ayın özeti ve son 6 ay grafiği
  - Tanımlar: *Ciro* = tamamlanan tedavi tutarı · *Tahsilat* = alınan ödemeler · *Net* = tahsilat − gider
- **Hızlı arama:** üst bardan her sayfada hasta araması (klavye ile seçim)
- **Tedaviler:** hastaya uygulanan işlemler (diş numarası, ücret, durum), randevudan tedavi oluşturma
- **İşlem tanımları:** Dolgu, Kanal tedavisi, İmplant vb. katalog ve varsayılan ücretler
- **Ödemeler:** tahsilat, yöntem, tarih aralığı filtreleri, dönem toplamı
- **Kullanıcılar ve klinik bilgileri:** yalnızca yönetici

## Yetkilendirme

Roller `users.role` alanında tutulur (`App\Enums\UserRole`). Kurallar `app/Policies` altındaki Policy sınıflarındadır:

| İşlem                          | Yönetici | Sekreter | Doktor                 |
|--------------------------------|:--------:|:--------:|:----------------------:|
| Hasta görüntüleme / ekleme     | ✓        | ✓        | ✓                      |
| Hasta silme                    | ✓        |          |                        |
| Doktor yönetimi                | ✓        |          |                        |
| Randevu oluşturma / düzenleme  | ✓        | ✓        |                        |
| Randevu durumu değiştirme      | ✓        | ✓        | Yalnızca kendi randevusu |
| Tedavi kaydı                   | ✓        |          | Yalnızca kendi adına   |
| Ödemeler                       | ✓        | ✓ (ekleme) |                      |
| Kullanıcılar, klinik bilgileri, işlem tanımları, raporlar | ✓ |   |                |
| Belge yükleme                  | ✓        | ✓        | ✓                      |
| Belge silme                    | ✓        | Kendi yüklediği | Kendi yüklediği |

Pasif hesaplar giriş yapamaz; oturum açıkken pasifleştirilen kullanıcı otomatik olarak çıkış yaptırılır.
Herkese açık kayıt kapalıdır.

## Veri modeli

```
users ─┬─< doctors (user_id, opsiyonel)
       ├─< appointments.created_by
       └─< payments.received_by
patients ─┬─< appointments >── doctors
          ├─< treatments >──── doctors, treatment_types, appointments (ops.)
          ├─< payments
          └─< patient_documents
clinic_settings (tek satır)
```

Hasta bakiyesi ayrı bir sütunda tutulmaz. İptal edilmemiş tedavilerin toplamından ödemeler düşülerek hesaplanır
(`Patient::scopeWithBalance`). Hastalar ve doktorlar soft delete ile arşivlenir, böylece geçmiş kayıtlar korunur.
