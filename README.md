# 🚀 GOOIDA - Zero-Bloat WordPress Platform

**Çekirdek Felsefe**: Sıfır şişkinlik, maksimum performans, programmatik SEO gücü ve gelir odaklı mimarı.

## 📋 Proje Özeti

GOOIDA, WordPress üzerine inşa edilmiş fakat WordPress'in kısıtlamalarından kurtulmuş, tamamen özel SQL mimarisi ve PHP 8.1+ optimizasyonuyla çalışan modern bir platform.

- **WordPress Versiyonu**: 7.0+
- **PHP Versiyonu**: 8.1+
- **Veritabanı**: MySQL 5.7+
- **Tema**: Custom Zero-Bloat Theme
- **Plugin**: gooida-custom (PSR-4 Architecture)

## 🏗️ Sistem Mimarisi

### Çekirdek Özellikler

✅ **Zero-Bloat Design**
- WPBakery, Elementor yok
- Custom tema ve SEO eklentisi
- Hafif ve hızlı

✅ **Custom SQL Mimarisi**
- `wp_postmeta` yerine bağımsız tablolar
- Direct `$wpdb` sınıfı kullanımı
- 9 custom tablo yapısı

✅ **Sezon Motoru (Dinamik Vitrin)**
- Mayıs-Eylül: Yaz Modu (Konaklama, Etkinlik, Plaj)
- Ekim-Nisan: Kış Modu (Emlak, Kampanyalar, Esnaf)
- Otomatik geçişler

✅ **VIP Bilgi Maskeleme**
- Free/Verified: Adres, telefon, konum maskelenmiş
- Premium (VIP): Tüm bilgiler açık

✅ **Instagram Reels Entegrasyonu**
- 9:16 oranı dikey videolar
- Lazy-load facade
- PageSpeed optimizasyonu

✅ **Onay Mekanizması (Staging)**
- 7 gün kilidi
- JSON revizyon sistemi
- Canlı içerik korunması

✅ **Reklam Motoru**
- Kategori bazlı dinamik fiyatlandırma
- Çapraz reklam sistemi
- Otomatik sürü takibi

✅ **Programmatik SEO (Rehber)**
- İç linkleme stratejisi
- Dinamik firma kartları (shortcode)
- Bölgesel micro-content

## 📁 Proje Yapısı

```
gooida/
├── database/
│   └── schema.sql                 # Master DB şema (9 tablo)
├── docs/
│   ├── ARCHITECTURE.md            # Sistem mimarisi
│   ├── DATABASE_SCHEMA.md         # Detaylı tablo açıklamaları
│   ├── API_ENDPOINTS.md           # REST API endpoints
│   └── SECURITY.md                # Güvenlik protokolleri
├── plugins/
│   └── gooida-custom/
│       ├── gooida-custom.php      # Plugin entrypoint
│       ├── includes/
│       │   ├── Autoloader.php
│       │   ├── Plugin.php
│       │   ├── Database.php
│       │   ├── SeasonEngine.php
│       │   ├── ImageProcessor.php
│       │   ├── ApprovalCycle.php
│       │   ├── AdManager.php
│       │   └── VIPMasking.php
│       └── admin/
│           ├── dashboard.php
│           └── settings.php
├── theme/
│   └── gooida-theme/
│       ├── functions.php
│       ├── style.css
│       └── templates/
├── config/
│   ├── database.php               # DB konfigürasyonu
│   └── constants.php              # Global sabitler
├── .github/
│   ├── ISSUE_TEMPLATE/
│   ├── workflows/
│   └── pull_request_template.md
└── .gitignore
```

## 🗄️ Veritabanı Tabloları (9 Ana Tablo)

### 1. **wp_gooida_kategoriler**
Kategori yönetimi (Konaklama, Etkinlik, Plaj, Emlak, Esnaf)

### 2. **wp_gooida_firmalar**
işletme/firma bilgileri, üyelik tipi, onay mekanizması

### 3. **wp_gooida_firmalar_resimleri**
Firma galerisi (1x1 kare, 700x700px JPG)

### 4. **wp_gooida_ilanlar**
Sahibinden tarzı ilanlar (Araç, Gayrimenkul, Eşya)

### 5. **wp_gooida_reklam_alanlari**
Reklam bölgeleri (Banner, Sidebar, Inline, Footer)

### 6. **wp_gooida_reklamlar**
Aktif reklam kampanyaları ve fiyatlandırma

### 7. **wp_gooida_odeme_bildirimleri**
Manuel havale/EFT bildirimleri (Canlı POS yok)

### 8. **wp_gooida_rehber**
Programmatik SEO blog içeriği (Mikro-bölgesel)

### 9. **wp_gooida_sezon_ayarlari**
Sezon tanımları ve tarih aralıkları

## 🔑 Kritik Mekanizmalar

### Görsel İşleme (ImageProcessor.php)
```php
// 700x700px JPG otomatik ölçekleme
// MIME-type kontrolü (sadece JPG)
// Orijinal silinme (disk tasarrufu)
```

### 7 Gün Kilidi
```php
// Timestamp tabanlı güncelleme koruma
// Yönetici dahil herkes için geçerli
// Veritabanında son_guncelleme alanı kontrol edilir
```

### Onay Mekanizması
```php
// Revizyon -> JSON alanına yazılır
// Yayında olan eski veri korunur
// Yönetici onayı sonrası canlıya çıkar
```

### Reklam Sürü Takibi
```php
// bitmis_tarihi saniyeyle kontrol edilir
// Otomatik kaldırma
// Yönetici panelinde kırmızı alarm
```

## 🚀 Kurulum

### 1. WordPress Kurulumu
```bash
wp core download --locale=tr_TR
wp config create --dbname=gooida --dbuser=root
wp db create
wp core install --url=http://gooida.local --title=GOOIDA --admin_user=admin --admin_email=admin@gooida.local
```

### 2. Veritabanı Şeması
```bash
wp db query < database/schema.sql
```

### 3. Plugin Aktivasyonu
```bash
# wp-content/plugins/gooida-custom/ içine yükle
wp plugin activate gooida-custom
```

### 4. Tema Aktivasyonu
```bash
# wp-content/themes/gooida-theme/ içine yükle
wp theme activate gooida-theme
```

## 📊 Performans Hedefleri

- **PageSpeed**: 95+
- **Core Web Vitals**: Optimize
- **Database Queries**: <10 / sayfa
- **Sayfa Yükü Süresi**: <1.5s

## 🔒 Güvenlik

- Sadece JPG MIME-type kabul (Görsel)
- SQL Injection koruması (Prepared Statements)
- XSS koruması (Sanitization)
- CSRF tokens (Formlar)
- Rol-tabanlı erişim kontrol

## 📝 Dokümantasyon

- [System Architecture](docs/ARCHITECTURE.md)
- [Database Schema](docs/DATABASE_SCHEMA.md)
- [API Endpoints](docs/API_ENDPOINTS.md)
- [Security Protocols](docs/SECURITY.md)

## 🤝 Katkıda Bulunma

Tek kişi projesi - Pull requests ve Issues'lar welcome!

## 📞 İletişim

- **GitHub**: [@nazmiemre-szr](https://github.com/nazmiemre-szr)
- **Platform**: GOOIDA

---

**Made with ❤️ | Zero-Bloat WordPress Architecture | PHP 8.1+ | MySQL 5.7+**
