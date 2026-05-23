<?php
/**
 * VIP Masking - Bilgi Gizleme Sistemi
 * 
 * Üyelik tipine göre firma bilgilerini gizle/göster:
 * - Free: Adres, telefon, konum maskelenmiş
 * - Verified: Adres maskelenmiş, telefon kısmi görünür
 * - Premium (VIP): Tüm bilgiler açık
 * 
 * @package GOOIDA
 * @subpackage VIPMasking
 * @since 1.0.0
 */

namespace GOOIDA;

class VIPMasking {
    /**
     * Get masked firma data for display
     * 
     * Firma verisini üyelik seviyesine göre maskeleyerek döndür
     *
     * @param object|array $firma Firma nesnesi veya array
     * @param string $context 'list' (listede) veya 'detail' (detay sayfasında)
     * @return object Maskelenmiş firma nesnesi
     */
    public static function get_masked_firma( $firma, string $context = 'detail' ) {
        // Array ise object'e çevir
        if ( is_array( $firma ) ) {
            $firma = (object) $firma;
        }

        // Üyelik tipine göre maskeleme
        switch ( $firma->uyelik_tipi ) {
            case GOOIDA_MEMBERSHIP_FREE:
                return self::mask_free_firma( $firma, $context );
            
            case GOOIDA_MEMBERSHIP_VERIFIED:
                return self::mask_verified_firma( $firma, $context );
            
            case GOOIDA_MEMBERSHIP_PREMIUM:
                return self::get_full_firma( $firma );
            
            default:
                return self::mask_free_firma( $firma, $context );
        }
    }

    /**
     * Mask free membership firma
     * 
     * Free üyelerin tüm hassas bilgileri maskelenmiş
     *
     * @param object $firma Firma nesnesi
     * @param string $context Display context
     * @return object Maskelenmiş firma
     */
    private static function mask_free_firma( $firma, string $context = 'detail' ) {
        $firma_copy = clone $firma;

        // Telefon tamamen gizle
        $firma_copy->telefon = self::mask_phone();
        
        // Email gizle (gösterme/yönlendir)
        $firma_copy->email = self::get_contact_button_text();
        
        // Adres tamamen gizle
        $firma_copy->il = '***';
        $firma_copy->ilce = '***';
        $firma_copy->mahalle = '***';
        $firma_copy->adres_detay = 'Adres görüntülemek için Premium üyeliğe geçiniz.';
        
        // Koordinatlar gizle
        $firma_copy->latitude = null;
        $firma_copy->longitude = null;
        $firma_copy->has_location = false;
        
        // Website'i göstere bilirsin ama tıklamada yönlendir (tracking)
        if ( ! empty( $firma_copy->website ) ) {
            $firma_copy->website_display = 'Web Sitesi Ziyaret Et';
            $firma_copy->website_tracking = self::get_tracking_url( $firma_copy->website, $firma_copy->id );
        }
        
        // Instagram kısıtla
        if ( ! empty( $firma_copy->instagram_handle ) ) {
            $firma_copy->instagram_reels_json = null;
        }

        $firma_copy->membership_badge = 'free';
        $firma_copy->is_vip = false;
        $firma_copy->can_view_full_contact = false;

        return $firma_copy;
    }

    /**
     * Mask verified membership firma
     * 
     * Verified üyelerin telefon kısmi görünür, adres maskelenmiş
     *
     * @param object $firma Firma nesnesi
     * @param string $context Display context
     * @return object Maskelenmiş firma
     */
    private static function mask_verified_firma( $firma, string $context = 'detail' ) {
        $firma_copy = clone $firma;

        // Telefon kısmi maskelenmiş (son 4 hane göster)
        if ( ! empty( $firma_copy->telefon ) ) {
            $firma_copy->telefon_masked = self::mask_phone_partial( $firma_copy->telefon );
            $firma_copy->telefon_full = $firma_copy->telefon; // Backend'de tam numara saklı
        }
        
        // Email göster
        $firma_copy->email_masked = self::mask_email( $firma_copy->email );
        
        // Adres kısmi maskelenmiş (il/ilçe göster)
        if ( ! empty( $firma_copy->il ) ) {
            $firma_copy->il_display = $firma_copy->il;
            $firma_copy->ilce_display = $firma_copy->ilce;
        }
        $firma_copy->mahalle = '***';
        $firma_copy->adres_detay = 'Tam adres görüntülemek için Premium üyeliğe geçiniz.';
        
        // Koordinatlar gizle
        $firma_copy->latitude = null;
        $firma_copy->longitude = null;
        $firma_copy->has_location = false;
        
        // Website göster
        $firma_copy->website_display = ! empty( $firma_copy->website ) ? $firma_copy->website : null;
        
        // Instagram göster ama video embed'i kısıt
        if ( ! empty( $firma_copy->instagram_reels_json ) ) {
            $firma_copy->instagram_reels_limited = true;
        }

        $firma_copy->membership_badge = 'verified';
        $firma_copy->is_vip = false;
        $firma_copy->can_view_full_contact = false;

        return $firma_copy;
    }

    /**
     * Get full firma data (Premium/VIP)
     * 
     * Premium üyelik - Tüm bilgiler açık
     *
     * @param object $firma Firma nesnesi
     * @return object Premium firma
     */
    private static function get_full_firma( $firma ) {
        $firma_copy = clone $firma;

        // Tüm bilgiler açık
        $firma_copy->telefon_masked = null;
        $firma_copy->telefon_full = $firma_copy->telefon;
        $firma_copy->email_masked = null;
        $firma_copy->email_full = $firma_copy->email;
        
        // Adres tam görünür
        $firma_copy->il_display = $firma_copy->il;
        $firma_copy->ilce_display = $firma_copy->ilce;
        $firma_copy->mahalle_display = $firma_copy->mahalle;
        $firma_copy->adres_display = $firma_copy->adres_detay;
        
        // Koordinatlar açık
        $firma_copy->has_location = ! empty( $firma_copy->latitude ) && ! empty( $firma_copy->longitude );
        
        // Website tam
        $firma_copy->website_display = $firma_copy->website;
        
        // Instagram tam
        $firma_copy->instagram_reels_limited = false;

        $firma_copy->membership_badge = 'premium';
        $firma_copy->is_vip = true;
        $firma_copy->can_view_full_contact = true;

        return $firma_copy;
    }

    /**
     * Get masked firmalar list
     * 
     * Firmalar listesini maskeleyerek döndür
     *
     * @param array $firmalar Firmalar array
     * @return array Maskelenmiş firmalar
     */
    public static function get_masked_firmalar( array $firmalar ): array {
        return array_map( function ( $firma ) {
            return self::get_masked_firma( $firma, 'list' );
        }, $firmalar );
    }

    /**
     * Check if user can view full contact
     * 
     * Kullanıcı tam iletişim bilgisini görebilir mi?
     *
     * @param int $firma_id Firma ID
     * @param int $user_id User ID (Optional)
     * @return bool Can view
     */
    public static function can_view_full_contact( int $firma_id, int $user_id = 0 ): bool {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        // Admin her zaman görebilir
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        // Firma sahibi kendi firmasını görebilir
        $firma = Database::get_firma( $firma_id );
        if ( $firma && $firma->user_id == $user_id ) {
            return true;
        }

        // Premium üyelik kontrol et
        if ( $firma && GOOIDA_MEMBERSHIP_PREMIUM === $firma->uyelik_tipi ) {
            return true;
        }

        return false;
    }

    /**
     * Get phone mask
     * 
     * Telefonun tamamını gizle
     *
     * @return string Masked phone
     */
    private static function mask_phone(): string {
        return '*** *** ****';
    }

    /**
     * Mask phone partially
     * 
     * Telefon numarasını kısmi maskeyle (son 4 hane)
     *
     * @param string $phone Telefon numarası
     * @return string Masked phone
     */
    private static function mask_phone_partial( string $phone ): string {
        $phone_clean = preg_replace( '/[^0-9]/', '', $phone );
        
        if ( strlen( $phone_clean ) < 4 ) {
            return '***';
        }

        $last_four = substr( $phone_clean, -4 );
        $mask = str_repeat( '*', strlen( $phone_clean ) - 4 );
        
        return $mask . $last_four;
    }

    /**
     * Mask email
     * 
     * Email adresini maskeyle
     *
     * @param string $email Email adresi
     * @return string Masked email
     */
    private static function mask_email( string $email ): string {
        $parts = explode( '@', $email );
        
        if ( count( $parts ) !== 2 ) {
            return '***@***.***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        // Local part maskeyle
        if ( strlen( $local ) > 2 ) {
            $masked_local = substr( $local, 0, 2 ) . str_repeat( '*', strlen( $local ) - 2 );
        } else {
            $masked_local = '*' . substr( $local, -1 );
        }

        return $masked_local . '@***';
    }

    /**
     * Get contact button text
     * 
     * İletişim düğmesinin yazısını getir
     *
     * @return string Button text
     */
    private static function get_contact_button_text(): string {
        return 'İletişim için Bize Yazın';
    }

    /**
     * Get tracking URL
     * 
     * External link takibi için URL oluştur
     *
     * @param string $url Orijinal URL
     * @param int $firma_id Firma ID
     * @return string Tracking URL
     */
    private static function get_tracking_url( string $url, int $firma_id ): string {
        $tracking_key = wp_hash( "{$firma_id}-{$url}" );
        return home_url( "/gooida-track/?url=" . urlencode( $url ) . "&firma_id=" . $firma_id . "&key=" . $tracking_key );
    }

    /**
     * Get membership upgrade CTA
     * 
     * Üyelik yükselt call-to-action
     *
     * @param string $membership_type Mevcut üyelik tipi
     * @return array CTA bilgisi
     */
    public static function get_upgrade_cta( string $membership_type ): array {
        $cta = [
            GOOIDA_MEMBERSHIP_FREE => [
                'text' => 'Verified Üyeliğe Geçin - 3 Fotoğraf Yükleyin',
                'link' => home_url( '/pricing#verified' ),
                'icon' => 'verified',
            ],
            GOOIDA_MEMBERSHIP_VERIFIED => [
                'text' => 'Premium Üyeliğe Yükseltin - Tam Bilgiler Gösterini',
                'link' => home_url( '/pricing#premium' ),
                'icon' => 'premium',
            ],
        ];

        return $cta[ $membership_type ] ?? [];
    }

    /**
     * Get membership info
     * 
     * Üyelik bilgisini getir (badge, benefits, etc)
     *
     * @param string $membership_type Üyelik tipi
     * @return array Membership info
     */
    public static function get_membership_info( string $membership_type ): array {
        $info = [
            GOOIDA_MEMBERSHIP_FREE => [
                'name' => 'Ücretsiz',
                'badge' => '🔓',
                'benefits' => [
                    '0 Fotoğraf',
                    'Telefon Gizli',
                    'Adres Gizli',
                    'Konum Gizli',
                ],
                'color' => '#999999',
            ],
            GOOIDA_MEMBERSHIP_VERIFIED => [
                'name' => 'Doğrulanmış',
                'badge' => '✓',
                'benefits' => [
                    '3 Fotoğraf',
                    'Telefon Kısmi Görünür',
                    'Adres Kısmi Görünür',
                    'Konum Gizli',
                ],
                'color' => '#3498db',
            ],
            GOOIDA_MEMBERSHIP_PREMIUM => [
                'name' => 'Premium',
                'badge' => '★',
                'benefits' => [
                    '30 Fotoğraf',
                    'Telefon Tam Görünür',
                    'Adres Tam Görünür',
                    'Konum Görünür',
                    'Reklam Alanları',
                    'Öne Çıkarma',
                ],
                'color' => '#f39c12',
            ],
        ];

        return $info[ $membership_type ] ?? $info[ GOOIDA_MEMBERSHIP_FREE ];
    }

    /**
     * Apply masking to firma gallery
     * 
     * Firma galerisine maskeleme uygula
     *
     * @param int $firma_id Firma ID
     * @param array $images Resim listesi
     * @return array Maskelenmiş resim listesi
     */
    public static function mask_gallery( int $firma_id, array $images ): array {
        $firma = Database::get_firma( $firma_id );
        if ( ! $firma ) {
            return [];
        }

        if ( GOOIDA_MEMBERSHIP_FREE === $firma->uyelik_tipi ) {
            return []; // Free üyeler resim yükleyemez
        }

        return $images;
    }

    /**
     * Get visible image count
     * 
     * Görünebilir resim sayısını getir
     *
     * @param string $membership_type Üyelik tipi
     * @return int Resim sayısı
     */
    public static function get_visible_image_count( string $membership_type ): int {
        switch ( $membership_type ) {
            case GOOIDA_MEMBERSHIP_FREE:
                return GOOIDA_FREE_MAX_IMAGES;
            
            case GOOIDA_MEMBERSHIP_VERIFIED:
                return GOOIDA_VERIFIED_MAX_IMAGES;
            
            case GOOIDA_MEMBERSHIP_PREMIUM:
                return GOOIDA_PREMIUM_MAX_IMAGES;
            
            default:
                return GOOIDA_FREE_MAX_IMAGES;
        }
    }

    /**
     * Can upload images
     * 
     * Kullanıcı resim yükleyebilir mi?
     *
     * @param string $membership_type Üyelik tipi
     * @return bool Can upload
     */
    public static function can_upload_images( string $membership_type ): bool {
        return self::get_visible_image_count( $membership_type ) > 0;
    }

    /**
     * Should show upgrade prompt
     * 
     * Yükseltme promptunu göster mi?
     *
     * @param string $membership_type Üyelik tipi
     * @param string $feature_requested İstenen özellik
     * @return array Prompt bilgisi veya empty array
     */
    public static function should_show_upgrade_prompt( string $membership_type, string $feature_requested = '' ): array {
        $prompts = [
            GOOIDA_MEMBERSHIP_FREE => [
                'show' => true,
                'title' => 'Firmanızı Daha Görünür Kılın',
                'message' => 'Premium üyeliğe geçerek tam iletişim bilgilerinizi, 30 fotoğraf ve öne çıkarma özelliğine erişin.',
                'cta' => 'Premium Üyeliğe Yükseltin',
            ],
            GOOIDA_MEMBERSHIP_VERIFIED => [
                'show' => true,
                'title' => 'Tüm Avantajlardan Yararlanın',
                'message' => 'Premium üyeliğe yükseltin ve tüm iletişim bilgilerinizi, 30 fotoğraf ve öne çıkarma özelliğini kullanın.',
                'cta' => 'Premium Üyeliğe Yükseltin',
            ],
            GOOIDA_MEMBERSHIP_PREMIUM => [
                'show' => false,
            ],
        ];

        return $prompts[ $membership_type ] ?? [];
    }
}
