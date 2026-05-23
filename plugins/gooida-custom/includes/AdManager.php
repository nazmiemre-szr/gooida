<?php
/**
 * Ad Manager - Reklam Motoru
 * 
 * Dinamik reklam fiyatlandırması, kategori bazlı tariffler,
 * otomatik sürü takibi, çapraz reklam sistemi
 * 
 * @package GOOIDA
 * @subpackage AdManager
 * @since 1.0.0
 */

namespace GOOIDA;

class AdManager {
    /**
     * Create ad campaign
     * 
     * Yeni reklam kampanyası oluştur
     *
     * @param int $firma_id Firma ID
     * @param int $reklam_alani_id Reklam alanı ID
     * @param array $ad_data Reklam verisi
     * @return array ['success' => bool, 'message' => string, 'ad_id' => int|null]
     */
    public static function create_ad_campaign( int $firma_id, int $reklam_alani_id, array $ad_data ): array {
        global $wpdb;

        // Firma kontrol et
        $firma = Database::get_firma( $firma_id );
        if ( ! $firma ) {
            return [
                'success' => false,
                'message' => 'Firma bulunamadı.',
                'ad_id' => null,
            ];
        }

        // Üyelik kontrolü (Premium / Verified)
        if ( ! in_array( $firma->uyelik_tipi, [ GOOIDA_MEMBERSHIP_VERIFIED, GOOIDA_MEMBERSHIP_PREMIUM ], true ) ) {
            return [
                'success' => false,
                'message' => 'Reklam yayınlamak için Premium veya Verified üyelik gereklidir.',
                'ad_id' => null,
            ];
        }

        // Reklam alanı kontrol et
        $reklam_alani = self::get_reklam_alani( $reklam_alani_id );
        if ( ! $reklam_alani ) {
            return [
                'success' => false,
                'message' => 'Reklam alanı bulunamadı.',
                'ad_id' => null,
            ];
        }

        // Veriyi sanitize et
        $sanitized_data = self::sanitize_ad_data( $ad_data );

        // Fiyat hesapla
        $price_calculation = self::calculate_ad_price( 
            $firma->kategori_id, 
            $reklam_alani_id, 
            $sanitized_data['duration_days'] ?? 30
        );

        if ( ! $price_calculation['success'] ) {
            return [
                'success' => false,
                'message' => 'Fiyat hesaplanamadı.',
                'ad_id' => null,
            ];
        }

        // Bitiş tarihi hesapla
        $duration_days = $sanitized_data['duration_days'] ?? 30;
        $start_date = current_time( 'mysql' );
        $end_date = wp_date( 'Y-m-d H:i:s', strtotime( "+{$duration_days} days", current_time( 'timestamp' ) ) );

        // Kampanyayı veritabanına ekle
        $insert_data = [
            'firma_id' => $firma_id,
            'reklam_alani_id' => $reklam_alani_id,
            'baslik' => $sanitized_data['baslik'],
            'aciklama' => $sanitized_data['aciklama'],
            'resim_url' => $sanitized_data['resim_url'],
            'hedef_url' => $sanitized_data['hedef_url'],
            'baslamis_tarihi' => $start_date,
            'bitmis_tarihi' => $end_date,
            'sures_gun' => $duration_days,
            'kategori_bazli_fiyat' => $price_calculation['price_per_day'],
            'toplam_ucret' => $price_calculation['total_price'],
            'odeme_durumu' => GOOIDA_AD_STATUS_PENDING,
            'odeme_turu' => $sanitized_data['payment_type'] ?? GOOIDA_PAYMENT_TYPE_TRANSFER,
            'aktif' => false, // Ödeme onayına kadar pasif
        ];

        $result = $wpdb->insert(
            $wpdb->prefix . 'gooida_reklamlar',
            $insert_data,
            [
                '%d', '%d', '%s', '%s', '%s', '%s', 
                '%s', '%s', '%d', '%f', '%f', '%s', '%s', '%d'
            ]
        );

        if ( false === $result ) {
            return [
                'success' => false,
                'message' => 'Reklam kampanyası oluşturulamadı.',
                'ad_id' => null,
            ];
        }

        $ad_id = $wpdb->insert_id;

        // Ödeme bildirimi ekle
        self::create_payment_notification( $firma_id, $ad_id, $price_calculation['total_price'] );

        return [
            'success' => true,
            'message' => sprintf(
                'Reklam kampanyası oluşturuldu. Toplam ücret: %s TL',
                number_format( $price_calculation['total_price'], 2, ',', '.' )
            ),
            'ad_id' => $ad_id,
            'pricing' => $price_calculation,
        ];
    }

    /**
     * Calculate ad price
     * 
     * Reklam fiyatını kategoriye ve alana göre hesapla
     *
     * @param int $kategori_id Kategori ID
     * @param int $reklam_alani_id Reklam alanı ID
     * @param int $duration_days Gün sayısı
     * @return array ['success' => bool, 'price_per_day' => float, 'total_price' => float]
     */
    public static function calculate_ad_price( int $kategori_id, int $reklam_alani_id, int $duration_days = 30 ): array {
        global $wpdb;

        // Reklam alanı getir
        $reklam_alani = self::get_reklam_alani( $reklam_alani_id );
        if ( ! $reklam_alani ) {
            return [
                'success' => false,
                'price_per_day' => 0,
                'total_price' => 0,
            ];
        }

        // Kategori bazlı tarife tablosu
        $category_price_multiplier = self::get_category_price_multiplier( $kategori_id );

        // Temel fiyat (aylık)
        $base_price_monthly = $reklam_alani->temel_fiyat_aylik ?? 1000;

        // Kategoriye göre çarpan uygula (bazı kategoriler daha pahalı)
        $adjusted_price_monthly = $base_price_monthly * $category_price_multiplier;

        // Günlük fiyat
        $price_per_day = $adjusted_price_monthly / 30;

        // Toplam fiyat
        $total_price = $price_per_day * $duration_days;

        // Discount kontrol et (uzun kampanyalar için)
        if ( $duration_days >= 90 ) {
            $total_price = $total_price * 0.85; // 15% indirim
        } elseif ( $duration_days >= 60 ) {
            $total_price = $total_price * 0.90; // 10% indirim
        }

        return [
            'success' => true,
            'price_per_day' => round( $price_per_day, 2 ),
            'total_price' => round( $total_price, 2 ),
            'base_price_monthly' => round( $base_price_monthly, 2 ),
            'category_multiplier' => $category_price_multiplier,
            'duration_days' => $duration_days,
        ];
    }

    /**
     * Get category price multiplier
     * 
     * Kategoriye göre fiyat çarpanını getir
     *
     * @param int $kategori_id Kategori ID
     * @return float Price multiplier
     */
    private static function get_category_price_multiplier( int $kategori_id ): float {
        global $wpdb;

        // Kategori getir
        $kategori = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gooida_kategoriler WHERE id = %d",
                $kategori_id
            )
        );

        if ( ! $kategori ) {
            return 1.0; // Varsayılan
        }

        // Kategori bazlı fiyatlandırma
        $multipliers = [
            'konaklama' => 1.5,    // +50%
            'etkinlik' => 1.3,     // +30%
            'plaj' => 1.2,         // +20%
            'emlak' => 2.0,        // +100% (Yüksek değer)
            'esnaf' => 0.8,        // -20%
        ];

        return $multipliers[ $kategori->kategori_slug ] ?? 1.0;
    }

    /**
     * Approve ad payment
     * 
     * Reklam ödemesini onayla ve aktif et
     *
     * @param int $ad_id Reklam ID
     * @return array ['success' => bool, 'message' => string]
     */
    public static function approve_ad_payment( int $ad_id ): array {
        global $wpdb;

        // Yönetici kontrolü
        if ( ! current_user_can( 'manage_options' ) ) {
            return [
                'success' => false,
                'message' => 'Bu işlem için yönetici izni gereklidir.',
            ];
        }

        // Reklam getir
        $ad = self::get_ad( $ad_id );
        if ( ! $ad ) {
            return [
                'success' => false,
                'message' => 'Reklam bulunamadı.',
            ];
        }

        // Üst tarih geçtiyse güncelle
        if ( strtotime( $ad->bitmis_tarihi ) < time() ) {
            return [
                'success' => false,
                'message' => 'Reklamın süresi dolmuş. Yeni kampanya oluşturun.',
            ];
        }

        // Öde
        $update_result = $wpdb->update(
            $wpdb->prefix . 'gooida_reklamlar',
            [
                'odeme_durumu' => GOOIDA_AD_STATUS_APPROVED,
                'aktif' => true,
            ],
            [ 'id' => $ad_id ],
            [ '%s', '%d' ],
            [ '%d' ]
        );

        if ( false === $update_result ) {
            return [
                'success' => false,
                'message' => 'Ödeme onaylanamadı.',
            ];
        }

        // Log kaydı
        self::log_ad_action( $ad_id, 'payment_approved' );

        // Müşteriye bildirim gönder
        self::notify_customer_ad_active( $ad->firma_id, $ad_id );

        return [
            'success' => true,
            'message' => 'Reklam ödeme onaylandı ve yayında.',
        ];
    }

    /**
     * Check and deactivate expired ads
     * 
     * Süresi dolan reklamları otomatik deaktif et
     *
     * @return int Deactivated count
     */
    public static function check_expired_ads(): int {
        global $wpdb;

        $current_time = current_time( 'mysql' );

        // Süresi dolan aktif reklamları bul
        $expired_ads = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}gooida_reklamlar 
                 WHERE aktif = %d AND bitmis_tarihi <= %s",
                1,
                $current_time
            )
        );

        if ( empty( $expired_ads ) ) {
            return 0;
        }

        // Deaktif et
        $placeholders = implode( ',', array_fill( 0, count( $expired_ads ), '%d' ) );
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}gooida_reklamlar 
                 SET aktif = 0 
                 WHERE id IN ($placeholders)",
                ...$expired_ads
            )
        );

        return count( $expired_ads );
    }

    /**
     * Get active ads for display area
     * 
     * Reklam alanı için aktif reklamları getir (sıralı)
     *
     * @param int $reklam_alani_id Reklam alanı ID
     * @return array Aktif reklamlar
     */
    public static function get_active_ads( int $reklam_alani_id ): array {
        global $wpdb;

        $current_time = current_time( 'mysql' );

        $ads = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, f.firma_adi, f.sira_numarasi 
                 FROM {$wpdb->prefix}gooida_reklamlar r
                 JOIN {$wpdb->prefix}gooida_firmalar f ON r.firma_id = f.id
                 WHERE r.reklam_alani_id = %d 
                 AND r.aktif = %d 
                 AND r.bitmis_tarihi > %s
                 AND r.odeme_durumu = %s
                 ORDER BY f.sira_numarasi ASC, r.baslamis_tarihi DESC",
                $reklam_alani_id,
                1,
                $current_time,
                GOOIDA_AD_STATUS_APPROVED
            )
        );

        if ( ! $ads ) {
            return [];
        }

        // Reklam görüntülemelerini arttır
        foreach ( $ads as $ad ) {
            self::increment_impressions( $ad->id );
        }

        return $ads;
    }

    /**
     * Get random ad from category
     * 
     * Kategori için random reklam al (çapraz reklam)
     *
     * @param int $kategori_id Kategori ID
     * @return object|null Random ad
     */
    public static function get_random_ad_from_category( int $kategori_id ) {
        global $wpdb;

        $current_time = current_time( 'mysql' );

        $ad = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT r.*, f.firma_adi 
                 FROM {$wpdb->prefix}gooida_reklamlar r
                 JOIN {$wpdb->prefix}gooida_firmalar f ON r.firma_id = f.id
                 WHERE f.kategori_id = %d 
                 AND r.aktif = %d 
                 AND r.bitmis_tarihi > %s
                 AND r.odeme_durumu = %s
                 ORDER BY RAND()
                 LIMIT 1",
                $kategori_id,
                1,
                $current_time,
                GOOIDA_AD_STATUS_APPROVED
            )
        );

        if ( $ad ) {
            self::increment_impressions( $ad->id );
        }

        return $ad;
    }

    /**
     * Track ad click
     * 
     * Reklam tıklamasını takip et
     *
     * @param int $ad_id Reklam ID
     * @return string Redirect URL
     */
    public static function track_ad_click( int $ad_id ): string {
        global $wpdb;

        // Tık sayısını arttır
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}gooida_reklamlar 
                 SET clicks = clicks + 1 
                 WHERE id = %d",
                $ad_id
            )
        );

        // CTR'yi güncelle (Click Through Rate)
        self::update_ctr( $ad_id );

        // Reklam URL'sini getir
        $ad = self::get_ad( $ad_id );
        return $ad->hedef_url ?? home_url();
    }

    /**
     * Get pending ads for admin
     * 
     * Ödeme bekleyen reklamları getir (admin dashboard)
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Pending ads
     */
    public static function get_pending_ads( int $limit = 50, int $offset = 0 ): array {
        global $wpdb;

        $ads = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, f.firma_adi, f.email, ra.bolge_adi 
                 FROM {$wpdb->prefix}gooida_reklamlar r
                 JOIN {$wpdb->prefix}gooida_firmalar f ON r.firma_id = f.id
                 JOIN {$wpdb->prefix}gooida_reklam_alanlari ra ON r.reklam_alani_id = ra.id
                 WHERE r.odeme_durumu = %s
                 ORDER BY r.baslamis_tarihi DESC
                 LIMIT %d OFFSET %d",
                GOOIDA_AD_STATUS_PENDING,
                $limit,
                $offset
            )
        );

        return $ads ?? [];
    }

    /**
     * Get expired ads alert
     * 
     * Süresi dolan reklamları getir (admin alert)
     *
     * @return array Süresi dolan reklamlar
     */
    public static function get_expired_ads_alert(): array {
        global $wpdb;

        $current_time = current_time( 'mysql' );

        // 7 gün içinde bitmesi veya bitmiş olan reklamlar
        $expired = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, f.firma_adi, ra.bolge_adi,
                 DATEDIFF(r.bitmis_tarihi, %s) as days_remaining
                 FROM {$wpdb->prefix}gooida_reklamlar r
                 JOIN {$wpdb->prefix}gooida_firmalar f ON r.firma_id = f.id
                 JOIN {$wpdb->prefix}gooida_reklam_alanlari ra ON r.reklam_alani_id = ra.id
                 WHERE r.aktif = %d 
                 AND r.bitmis_tarihi <= DATE_ADD(%s, INTERVAL 7 DAY)
                 ORDER BY r.bitmis_tarihi ASC",
                $current_time,
                1,
                $current_time
            )
        );

        return $expired ?? [];
    }

    /**
     * Get ad statistics
     * 
     * Reklam istatistiklerini getir
     *
     * @param int $ad_id Reklam ID
     * @return array İstatistikler
     */
    public static function get_ad_statistics( int $ad_id ): array {
        $ad = self::get_ad( $ad_id );
        if ( ! $ad ) {
            return [];
        }

        $impressions = (int) $ad->impressions;
        $clicks = (int) $ad->clicks;
        $ctr = $impressions > 0 ? ( ( $clicks / $impressions ) * 100 ) : 0;

        return [
            'ad_id' => $ad_id,
            'firma_adi' => $ad->firma_adi,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => round( $ctr, 2 ),
            'cost' => $ad->toplam_ucret,
            'cost_per_click' => $clicks > 0 ? round( $ad->toplam_ucret / $clicks, 2 ) : 0,
            'start_date' => $ad->baslamis_tarihi,
            'end_date' => $ad->bitmis_tarihi,
            'status' => $ad->aktif ? 'Aktif' : 'İnaktif',
        ];
    }

    /**
     * Helper: Get ad
     *
     * @param int $ad_id Reklam ID
     * @return object|null Ad
     */
    private static function get_ad( int $ad_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gooida_reklamlar WHERE id = %d",
                $ad_id
            )
        );
    }

    /**
     * Helper: Get reklam alani
     *
     * @param int $reklam_alani_id Reklam alanı ID
     * @return object|null Reklam alanı
     */
    private static function get_reklam_alani( int $reklam_alani_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gooida_reklam_alanlari WHERE id = %d",
                $reklam_alani_id
            )
        );
    }

    /**
     * Increment impressions
     *
     * @param int $ad_id Reklam ID
     */
    private static function increment_impressions( int $ad_id ): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}gooida_reklamlar 
                 SET impressions = impressions + 1 
                 WHERE id = %d",
                $ad_id
            )
        );
    }

    /**
     * Update CTR (Click Through Rate)
     *
     * @param int $ad_id Reklam ID
     */
    private static function update_ctr( int $ad_id ): void {
        global $wpdb;

        $ad = self::get_ad( $ad_id );
        if ( ! $ad || $ad->impressions == 0 ) {
            return;
        }

        $ctr = ( $ad->clicks / $ad->impressions ) * 100;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}gooida_reklamlar 
                 SET ctr = %f 
                 WHERE id = %d",
                $ctr,
                $ad_id
            )
        );
    }

    /**
     * Sanitize ad data
     *
     * @param array $data Ad verisi
     * @return array Sanitized data
     */
    private static function sanitize_ad_data( array $data ): array {
        return [
            'baslik' => sanitize_text_field( $data['baslik'] ?? '' ),
            'aciklama' => sanitize_textarea_field( $data['aciklama'] ?? '' ),
            'resim_url' => esc_url( $data['resim_url'] ?? '' ),
            'hedef_url' => esc_url( $data['hedef_url'] ?? '' ),
            'duration_days' => (int) ( $data['duration_days'] ?? 30 ),
            'payment_type' => sanitize_text_field( $data['payment_type'] ?? GOOIDA_PAYMENT_TYPE_TRANSFER ),
        ];
    }

    /**
     * Create payment notification
     *
     * @param int $firma_id Firma ID
     * @param int $ad_id Reklam ID
     * @param float $amount Tutar
     */
    private static function create_payment_notification( int $firma_id, int $ad_id, float $amount ): void {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'gooida_odeme_bildirimleri',
            [
                'firma_id' => $firma_id,
                'islem_turu' => 'reklam',
                'transfer_tutari' => $amount,
                'onay_durumu' => GOOIDA_AD_STATUS_PENDING,
            ],
            [ '%d', '%s', '%f', '%s' ]
        );
    }

    /**
     * Log ad action
     *
     * @param int $ad_id Reklam ID
     * @param string $action Aksiyon
     */
    private static function log_ad_action( int $ad_id, string $action ): void {
        // Log tutmak isterseniz ekleyin
    }

    /**
     * Notify customer ad active
     *
     * @param int $firma_id Firma ID
     * @param int $ad_id Reklam ID
     */
    private static function notify_customer_ad_active( int $firma_id, int $ad_id ): void {
        $firma = Database::get_firma( $firma_id );
        if ( ! $firma ) {
            return;
        }

        $subject = '[GOOIDA] Reklam Kampanyanız Yayında';
        $message = sprintf(
            "Sayın,\n\n" .
            "Reklam kampanyanız başarıyla onaylanmış ve yayında.\n\n" .
            "Kampanya ID: %d\n" .
            "İstatistikleri görmek için kontrol panelini ziyaret edin.\n\n" .
            "Teşekkürler.",
            $ad_id
        );

        wp_mail( $firma->email, $subject, $message );
    }
}
