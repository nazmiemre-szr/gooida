-- ===================================================
-- GOOIDA - Master Database Schema
-- PHP 8.1+ | WordPress 7.0+
-- ===================================================

-- ===================================================
-- 1. KATEGORİ TABLOSU
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_kategoriler (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    kategori_adi VARCHAR(255) NOT NULL UNIQUE,
    kategori_slug VARCHAR(255) UNIQUE NOT NULL,
    aciklama TEXT,
    ikon_url VARCHAR(500),
    ana_kategori_id BIGINT UNSIGNED,
    sezon ENUM('yaz', 'kis', 'her_sezon') DEFAULT 'her_sezon',
    aktif BOOLEAN DEFAULT TRUE,
    olusturulma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ana_kategori_id) REFERENCES wp_gooida_kategoriler(id) ON DELETE SET NULL,
    INDEX idx_kategori_slug (kategori_slug),
    INDEX idx_sezon (sezon),
    INDEX idx_aktif (aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 2. İŞLETME / FİRMA TABLOSU
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_firmalar (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Temel Bilgiler
    firma_adi VARCHAR(255) NOT NULL,
    firma_slug VARCHAR(255) UNIQUE NOT NULL,
    aciklama LONGTEXT,
    logo_url VARCHAR(500),
    
    -- İletişim (VIP'ye göre maskelenecek)
    telefon VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    
    -- Adres (VIP'ye göre maskelenecek)
    il VARCHAR(100),
    ilce VARCHAR(100),
    mahalle VARCHAR(100),
    adres_detay TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Kategorisyon
    kategori_id BIGINT UNSIGNED,
    alt_kategori_id BIGINT UNSIGNED,
    
    -- Üyelik Tipi
    uyelik_tipi ENUM('free', 'verified', 'premium') DEFAULT 'free',
    uyelik_baslangic_tarihi DATETIME,
    uyelik_bitis_tarihi DATETIME,
    
    -- Sıralama ve Görünürlük
    sira_numarasi INT DEFAULT 999,
    yayinda BOOLEAN DEFAULT FALSE,
    
    -- Görsel Limitleri
    max_resim_sayisi INT DEFAULT 0,
    
    -- İnstagram Entegrasyonu
    instagram_handle VARCHAR(255),
    instagram_reels_json JSON,
    
    -- Onay Mekanizması
    onay_durumu ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Revizyon Mekanizması (7 gün kilidi)
    son_guncelleme DATETIME,
    revizyon_data JSON,
    revizyon_pending BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    olusturulma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    silinmi_mi BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES wp_gooida_kategoriler(id) ON DELETE SET NULL,
    FOREIGN KEY (alt_kategori_id) REFERENCES wp_gooida_kategoriler(id) ON DELETE SET NULL,
    
    INDEX idx_firma_slug (firma_slug),
    INDEX idx_user_id (user_id),
    INDEX idx_uyelik_tipi (uyelik_tipi),
    INDEX idx_sira_numarasi (sira_numarasi),
    INDEX idx_yayinda (yayinda),
    INDEX idx_son_guncelleme (son_guncelleme),
    INDEX idx_kategori_id (kategori_id),
    FULLTEXT INDEX ft_firma_adi (firma_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 3. FİRMA RESİMLERİ / GALERİ
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_firmalar_resimleri (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    firma_id BIGINT UNSIGNED NOT NULL,
    
    -- Resim (1x1 kare, 700x700px JPG)
    resim_url VARCHAR(500) NOT NULL,
    resim_baslik VARCHAR(255),
    alt_text VARCHAR(255),
    
    -- Sıralaması
    sira_numarasi INT DEFAULT 0,
    
    -- Metadata
    yuklenme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (firma_id) REFERENCES wp_gooida_firmalar(id) ON DELETE CASCADE,
    INDEX idx_firma_id (firma_id),
    INDEX idx_sira_numarasi (sira_numarasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 4. İLAN TABLOSU (Sahibinden Tarzı)
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_ilanlar (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Temel Bilgiler
    ilan_baslik VARCHAR(255) NOT NULL,
    ilan_slug VARCHAR(255) UNIQUE NOT NULL,
    ilan_aciklama LONGTEXT,
    
    -- Kategorisyon (3 kategori: Araç, Gayrimenkul, Eşya)
    kategori ENUM('arac', 'gayrimenkul', 'esya') NOT NULL,
    
    -- Fiyat
    fiyat DECIMAL(12, 2),
    para_birimi VARCHAR(10) DEFAULT 'TRY',
    
    -- Konum
    il VARCHAR(100),
    ilce VARCHAR(100),
    
    -- Görsel
    ana_resim_url VARCHAR(500),
    resim_listesi JSON,
    
    -- İletişim
    telefon VARCHAR(20),
    email VARCHAR(255),
    
    -- Durum
    durum ENUM('aktif', 'satis_yapildi', 'kaldirdi') DEFAULT 'aktif',
    yayinda BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    olusturulma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ilan_bitis_tarihi DATETIME,
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    INDEX idx_kategori (kategori),
    INDEX idx_durum (durum),
    INDEX idx_yayinda (yayinda),
    INDEX idx_olusturulma_tarihi (olusturulma_tarihi),
    INDEX idx_ilan_slug (ilan_slug),
    FULLTEXT INDEX ft_ilan_baslik (ilan_baslik, ilan_aciklama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 5. REKLAM ALANLARI (Bölge Tanımı)
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_reklam_alanlari (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Tanım
    bolge_adi VARCHAR(255) NOT NULL UNIQUE,
    bolge_kodu VARCHAR(100) UNIQUE NOT NULL,
    
    -- Bölge Yapısı
    sayfa_tipi ENUM('firma_detay', 'firma_listesi', 'ilan_detay', 'anasayfa', 'kategori') NOT NULL,
    hedef_kategori_id BIGINT UNSIGNED,
    
    -- Boyutlar (Dinamik)
    en INT,
    yukseklik INT,
    
    -- SEO ve Pozisyon
    pozisyon_turu ENUM('banner', 'sidebar', 'inline', 'footer') NOT NULL,
    display_order INT,
    
    -- Fiyatlandırma Tablosu
    temel_fiyat_aylik DECIMAL(10, 2),
    
    -- Durumu
    aktif BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (hedef_kategori_id) REFERENCES wp_gooida_kategoriler(id) ON DELETE SET NULL,
    INDEX idx_sayfa_tipi (sayfa_tipi),
    INDEX idx_aktif (aktif),
    UNIQUE INDEX uix_bolge_kodu (bolge_kodu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 6. REKLAM KAMPANYALARI
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_reklamlar (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    firma_id BIGINT UNSIGNED NOT NULL,
    reklam_alani_id BIGINT UNSIGNED NOT NULL,
    
    -- Reklam İçeriği
    baslik VARCHAR(255),
    aciklama TEXT,
    resim_url VARCHAR(500),
    hedef_url VARCHAR(500),
    
    -- Süresi
    baslamis_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    bitmis_tarihi DATETIME NOT NULL,
    sures_gun INT,
    
    -- Fiyatlandırma
    kategori_bazli_fiyat DECIMAL(10, 2) NOT NULL,
    toplam_ucret DECIMAL(10, 2),
    
    -- Ödeme Durumu
    odeme_durumu ENUM('beklemede', 'onaylandi', 'iptal') DEFAULT 'beklemede',
    odeme_turu ENUM('manuel_havale', 'kapida_odeme') DEFAULT 'manuel_havale',
    
    -- Görüntüleme İstatistikleri
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    ctr DECIMAL(5, 2),
    
    -- Durumu
    aktif BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (firma_id) REFERENCES wp_gooida_firmalar(id) ON DELETE CASCADE,
    FOREIGN KEY (reklam_alani_id) REFERENCES wp_gooida_reklam_alanlari(id) ON DELETE CASCADE,
    
    INDEX idx_firma_id (firma_id),
    INDEX idx_bitmis_tarihi (bitmis_tarihi),
    INDEX idx_aktif (aktif),
    INDEX idx_odeme_durumu (odeme_durumu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 7. ÖDEME BİLDİRİMLERİ (Manuel Havale/EFT)
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_odeme_bildirimleri (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    firma_id BIGINT UNSIGNED NOT NULL,
    
    -- Ödeme Bilgisi
    islem_turu ENUM('reklam', 'uyelik_yukselt', 'diger') NOT NULL,
    
    -- Transfer Detayları
    gonderici_adi VARCHAR(255),
    gonderici_banka_hesap_no VARCHAR(100),
    transfer_tarihi DATETIME,
    transfer_tutari DECIMAL(12, 2),
    transfer_referans_kodu VARCHAR(100) UNIQUE,
    
    -- Durum
    onay_durumu ENUM('beklemede', 'onaylandi', 'reddedildi') DEFAULT 'beklemede',
    
    -- Yönetim
    yonetici_notu TEXT,
    
    -- Metadata
    bildirim_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    onay_tarihi DATETIME,
    
    FOREIGN KEY (firma_id) REFERENCES wp_gooida_firmalar(id) ON DELETE CASCADE,
    INDEX idx_firma_id (firma_id),
    INDEX idx_onay_durumu (onay_durumu),
    INDEX idx_bildirim_tarihi (bildirim_tarihi),
    UNIQUE INDEX uix_transfer_referans (transfer_referans_kodu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 8. REHBER (Programmatik Blog - SEO Gücü)
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_rehber (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- İçerik
    baslik VARCHAR(500) NOT NULL UNIQUE,
    slug VARCHAR(500) UNIQUE NOT NULL,
    icerik LONGTEXT NOT NULL,
    
    -- Kategorisyon
    il VARCHAR(100),
    ilce VARCHAR(100),
    anahtar_kelimeler JSON,
    
    -- SEO
    meta_description VARCHAR(160),
    
    -- İç Linkleme (Firma ID'leri)
    linked_firma_ids JSON,
    
    -- Yayın
    yayinda BOOLEAN DEFAULT TRUE,
    olusturulma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- İstatistikler
    view_count INT DEFAULT 0,
    
    INDEX idx_slug (slug),
    INDEX idx_il (il),
    INDEX idx_ilce (ilce),
    INDEX idx_yayinda (yayinda),
    FULLTEXT INDEX ft_baslik_icerik (baslik, icerik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- 9. SEZON AYARLARI (Dinamik Vitrin Kontrolü)
-- ===================================================
CREATE TABLE IF NOT EXISTS wp_gooida_sezon_ayarlari (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Sezon Tanımı
    sezon_adi ENUM('yaz', 'kis') NOT NULL UNIQUE,
    
    -- Tarih Aralığı
    baslangic_ay INT,
    baslangic_gun INT,
    bitis_ay INT,
    bitis_gun INT,
    
    -- Ayarlar
    aktif BOOLEAN DEFAULT TRUE,
    
    INDEX idx_sezon_adi (sezon_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- BAŞLANGIÇ VERİLERİ - SEZON AYARLARI
-- ===================================================
INSERT INTO wp_gooida_sezon_ayarlari (sezon_adi, baslangic_ay, baslangic_gun, bitis_ay, bitis_gun, aktif) VALUES
('yaz', 5, 1, 9, 30, TRUE),
('kis', 10, 1, 4, 30, TRUE)
ON DUPLICATE KEY UPDATE sezon_adi = VALUES(sezon_adi);

-- ===================================================
-- BAŞLANGIÇ VERİLERİ - ÖRNEKLERİ KATEGORILER
-- ===================================================
INSERT INTO wp_gooida_kategoriler (kategori_adi, kategori_slug, aciklama, sezon, aktif) VALUES
('Konaklama', 'konaklama', 'Oteller, Pansiyon, Vilalar', 'yaz', TRUE),
('Etkinlik', 'etkinlik', 'Etkinlik ve Turizm', 'yaz', TRUE),
('Plaj', 'plaj', 'Plaj Işletmeleri', 'yaz', TRUE),
('Emlak', 'emlak', 'Gayrimenkul İlanları', 'kis', TRUE),
('Esnaf', 'esnaf', 'Yerel Esnaf ve Ticarethaneler', 'kis', TRUE)
ON DUPLICATE KEY UPDATE kategori_slug = VALUES(kategori_slug);

-- ===================================================
-- ENDEKSLERİN DOĞRULANMASI
-- ===================================================
-- Tüm temel indexler yukarıdaki tablo tanımlarında
-- yer almaktadır. Performans için Critical indexler:
-- - firma_slug (hızlı URL lookups)
-- - user_id (kullanıcı sorguları)
-- - sira_numarasi (sıralanmış listeleme)
-- - bitmis_tarihi (reklam süresi takibi)
-- - son_guncelleme (7 gün kilidi kontrolü)

-- ===================================================
-- SCHEMA VERSİYONU
-- ===================================================
-- Version: 1.0
-- Created: 2026-05-23
-- PHP: 8.1+
-- WordPress: 7.0+
-- ===================================================
