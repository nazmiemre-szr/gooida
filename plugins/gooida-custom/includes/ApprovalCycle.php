<?php
/**
 * Approval Cycle Manager - Onay Mekanizması
 * 
 * 7 gün kilidi + JSON revizyon sistemi
 * - Müşteri düzenleme -> revizyon_data (JSON) alanına yaz
 * - Eski/onaylı içerik yayında kal
 * - Yönetici onay sonrası canlıya çık
 * - 7 gün içinde tekrar düzenleme yapılamaz
 * 
 * @package GOOIDA
 * @subpackage ApprovalCycle
 * @since 1.0.0
 */

namespace GOOIDA;

class ApprovalCycle {
    /**
     * Submit firma for revision
     * 
     * Müşteri tarafından yapılan düzenleme revizyon sistemine alınır.
     * Yayındaki veri değişmez, revizyon_data JSON alanına yazılır.
     *
     * @param int $firma_id Firma ID
     * @param array $updated_data Güncellenen veri
     * @return array ['success' => bool, 'message' => string, 'revision_id' => int|null]
     */
    public static function submit_for_revision( int $firma_id, array $updated_data ): array {
        global $wpdb;

        // Firma ID doğrula
        $firma = Database::get_firma( $firma_id );
        if ( ! $firma ) {
            return [
                'success' => false,
                'message' => 'Firma bulunamadı.',
                'revision_id' => null,
            ];
        }

        // 7 gün kilidi kontrol et
        $lock_check = self::check_update_lock( $firma_id );
        if ( ! $lock_check['allowed'] ) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Bu firma %d gün daha güncellenemez. Son güncelleme: %s',
                    $lock_check['days_remaining'],
                    $lock_check['last_update']
                ),
                'revision_id' => null,
            ];
        }

        // Sanitize ve validate et
        $sanitized_data = self::sanitize_firma_data( $updated_data );

        // Revizyon JSON oluştur
        $revision = [
            'submitted_at' => current_time( 'mysql' ),
            'submitted_by_user_id' => get_current_user_id(),
            'submitted_by_email' => wp_get_current_user()->user_email,
            'data' => $sanitized_data,
            'status' => 'pending', // pending, approved, rejected
            'admin_notes' => '',
            'revision_hash' => wp_hash( wp_json_encode( $sanitized_data ) ),
        ];

        // revizyon_data alanına yaz
        $update_result = $wpdb->update(
            $wpdb->prefix . 'gooida_firmalar',
            [
                'revizyon_data' => wp_json_encode( $revision ),
                'revizyon_pending' => true,
            ],
            [ 'id' => $firma_id ],
            [ '%s', '%d' ],
            [ '%d' ]
        );

        if ( false === $update_result ) {
            return [
                'success' => false,
                'message' => 'Revizyon kaydedilemedi. Lütfen daha sonra tekrar deneyin.',
                'revision_id' => null,
            ];
        }

        // Admin'e bildirim gönder
        self::notify_admin_new_revision( $firma_id, $firma );

        return [
            'success' => true,
            'message' => 'Düzenlemeler başarıyla kaydedildi. Yönetici onayı bekleniyor.',
            'revision_id' => $firma_id,
        ];
    }

    /**
     * Approve revision
     * 
     * Yönetici tarafından revizyon onaylanır.
     * revizyon_data → ana tabloya taşı, revizyon_data temizle
     *
     * @param int $firma_id Firma ID
     * @param string $admin_notes Yönetici notları
     * @return array ['success' => bool, 'message' => string]
     */
    public static function approve_revision( int $firma_id, string $admin_notes = '' ): array {
        global $wpdb;

        // Yönetici kontrolü
        if ( ! current_user_can( 'manage_options' ) ) {
            return [
                'success' => false,
                'message' => 'Bu işlem için yönetici izni gereklidir.',
            ];
        }

        // Firma ve revizyon verisi getir
        $firma = Database::get_firma( $firma_id );
        if ( ! $firma ) {
            return [
                'success' => false,
                'message' => 'Firma bulunamadı.',
            ];
        }

        if ( ! $firma->revizyon_data ) {
            return [
                'success' => false,
                'message' => 'Bu firma için bekleyen revizyon yoktur.',
            ];
        }

        // JSON decode
        $revision = json_decode( $firma->revizyon_data, true );
        if ( ! $revision || ! isset( $revision['data'] ) ) {
            return [
                'success' => false,
                'message' => 'Revizyon verisi hatalı.',
            ];
        }

        // Revizyon datasını kanalize et
        $approved_data = $revision['data'];
        $approved_data['guncellenme_tarihi'] = current_time( 'mysql' );
        $approved_data['son_guncelleme'] = current_time( 'mysql' );

        // Ana tabloya taşı
        $update_result = $wpdb->update(
            $wpdb->prefix . 'gooida_firmalar',
            $approved_data,
            [ 'id' => $firma_id ],
            array_fill( 0, count( $approved_data ), '%s' ),
            [ '%d' ]
        );

        if ( false === $update_result ) {
            return [
                'success' => false,
                'message' => 'Revizyon onaylanamadı.',
            ];
        }

        // Revizyon temizle
        $wpdb->update(
            $wpdb->prefix . 'gooida_firmalar',
            [
                'revizyon_data' => null,
                'revizyon_pending' => false,
            ],
            [ 'id' => $firma_id ],
            [ '%s', '%d' ],
            [ '%d' ]
        );

        // Log kaydı oluştur
        self::log_revision_action( $firma_id, 'approved', $admin_notes );

        // Müşteriye bildirim gönder
        self::notify_customer_revision_approved( $firma_id, $firma );

        return [
            'success' => true,
            'message' => 'Revizyon başarıyla onaylandı ve yayında.',
        ];
    }

    /**
     * Reject revision
     * 
     * Yönetici tarafından revizyon reddedilir.
     * revizyon_data temizle, eski veri yayında kal
     *
     * @param int $firma_id Firma ID
     * @param string $rejection_reason Reddedilme nedeni
     * @return array ['success' => bool, 'message' => string]
     */
    public static function reject_revision( int $firma_id, string $rejection_reason = '' ): array {
        global $wpdb;

        // Yönetici kontrolü
        if ( ! current_user_can( 'manage_options' ) ) {
            return [
                'success' => false,
                'message' => 'Bu işlem için yönetici izni gereklidir.',
            ];
        }

        // Firma ve revizyon verisi getir
        $firma = Database::get_firma( $firma_id );
        if ( ! $firma ) {
            return [
                'success' => false,
                'message' => 'Firma bulunamadı.',
            ];
        }

        if ( ! $firma->revizyon_data ) {
            return [
                'success' => false,
                'message' => 'Bu firma için bekleyen revizyon yoktur.',
            ];
        }

        // Revizyon temizle
        $wpdb->update(
            $wpdb->prefix . 'gooida_firmalar',
            [
                'revizyon_data' => null,
                'revizyon_pending' => false,
            ],
            [ 'id' => $firma_id ],
            [ '%s', '%d' ],
            [ '%d' ]
        );

        // Log kaydı
        self::log_revision_action( $firma_id, 'rejected', $rejection_reason );

        // Müşteriye bildirim gönder
        self::notify_customer_revision_rejected( $firma_id, $firma, $rejection_reason );

        return [
            'success' => true,
            'message' => 'Revizyon başarıyla reddedildi.',
        ];
    }

    /**
     * Check 7-day update lock
     * 
     * Firma son 7 gün içinde güncellenmiş mi kontrol et
     * Yönetici dahil herkes için geçerli
     *
     * @param int $firma_id Firma ID
     * @return array ['allowed' => bool, 'days_remaining' => int, 'last_update' => string]
     */
    public static function check_update_lock( int $firma_id ): array {
        $firma = Database::get_firma( $firma_id );
        
        if ( ! $firma || ! $firma->son_guncelleme ) {
            return [
                'allowed' => true,
                'days_remaining' => 0,
                'last_update' => 'Hiçbir zaman',
            ];
        }

        $last_update = strtotime( $firma->son_guncelleme );
        $lock_until = $last_update + GOOIDA_UPDATE_LOCK_SECONDS;
        $current_time = time();

        if ( $current_time < $lock_until ) {
            $seconds_remaining = $lock_until - $current_time;
            $days_remaining = ceil( $seconds_remaining / DAY_IN_SECONDS );

            return [
                'allowed' => false,
                'days_remaining' => $days_remaining,
                'last_update' => wp_date( 'j F Y H:i', $last_update ),
            ];
        }

        return [
            'allowed' => true,
            'days_remaining' => 0,
            'last_update' => wp_date( 'j F Y H:i', $last_update ),
        ];
    }

    /**
     * Get pending revisions
     * 
     * Onay bekleyen tüm revisyonları getir
     *
     * @param int $limit Sonuç sayısı
     * @param int $offset Offset
     * @return array Revision array
     */
    public static function get_pending_revisions( int $limit = 50, int $offset = 0 ): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gooida_firmalar 
             WHERE revizyon_pending = %d 
             ORDER BY guncellenme_tarihi DESC 
             LIMIT %d OFFSET %d",
            1,
            $limit,
            $offset
        );

        $firmalar = $wpdb->get_results( $query );
        
        $result = [];
        foreach ( $firmalar as $firma ) {
            if ( $firma->revizyon_data ) {
                $result[] = [
                    'firma_id' => $firma->id,
                    'firma_adi' => $firma->firma_adi,
                    'user_email' => $firma->email,
                    'revision_data' => json_decode( $firma->revizyon_data, true ),
                    'submitted_at' => $firma->guncellenme_tarihi,
                ];
            }
        }

        return $result;
    }

    /**
     * Get revision count
     * 
     * Onay bekleyen revizyon sayısını getir
     *
     * @return int Count
     */
    public static function get_pending_revision_count(): int {
        global $wpdb;

        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gooida_firmalar 
             WHERE revizyon_pending = 1"
        );

        return (int) $count;
    }

    /**
     * Get revision history
     * 
     * Firma'nın revizyon geçmişini getir
     *
     * @param int $firma_id Firma ID
     * @param int $limit Limit
     * @return array Geçmiş kayıtları
     */
    public static function get_revision_history( int $firma_id, int $limit = 20 ): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gooida_revision_log 
             WHERE firma_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $firma_id,
            $limit
        );

        return $wpdb->get_results( $query ) ?? [];
    }

    /**
     * Sanitize firma data
     * 
     * Müşteri tarafından gelen veriyi temizle
     *
     * @param array $data Firma verisi
     * @return array Sanitized veri
     */
    private static function sanitize_firma_data( array $data ): array {
        $allowed_fields = [
            'firma_adi',
            'aciklama',
            'telefon',
            'email',
            'website',
            'il',
            'ilce',
            'mahalle',
            'adres_detay',
            'instagram_handle',
        ];

        $sanitized = [];
        foreach ( $allowed_fields as $field ) {
            if ( isset( $data[ $field ] ) ) {
                if ( 'email' === $field ) {
                    $sanitized[ $field ] = sanitize_email( $data[ $field ] );
                } elseif ( 'website' === $field ) {
                    $sanitized[ $field ] = esc_url( $data[ $field ] );
                } else {
                    $sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
                }
            }
        }

        return $sanitized;
    }

    /**
     * Log revision action
     * 
     * Revizyon aksiyonunu log'a kaydet
     *
     * @param int $firma_id Firma ID
     * @param string $action Action (approved, rejected, submitted)
     * @param string $notes Notlar
     */
    private static function log_revision_action( int $firma_id, string $action, string $notes = '' ): void {
        global $wpdb;

        // Eğer log tablosu yoksa oluştur
        $table_name = $wpdb->prefix . 'gooida_revision_log';
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            $wpdb->query( "
                CREATE TABLE IF NOT EXISTS $table_name (
                    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    firma_id BIGINT UNSIGNED NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    performed_by_user_id BIGINT UNSIGNED,
                    notes LONGTEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (firma_id) REFERENCES {$wpdb->prefix}gooida_firmalar(id) ON DELETE CASCADE,
                    INDEX idx_firma_id (firma_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            " );
        }

        $wpdb->insert(
            $table_name,
            [
                'firma_id' => $firma_id,
                'action' => sanitize_text_field( $action ),
                'performed_by_user_id' => get_current_user_id(),
                'notes' => sanitize_textarea_field( $notes ),
            ],
            [ '%d', '%s', '%d', '%s' ]
        );
    }

    /**
     * Notify admin of new revision
     * 
     * Yöneticiye yeni revizyon bildirimi gönder
     *
     * @param int $firma_id Firma ID
     * @param object $firma Firma nesnesi
     */
    private static function notify_admin_new_revision( int $firma_id, $firma ): void {
        $admin_email = get_option( 'admin_email' );
        $firma_link = admin_url( "admin.php?page=gooida-revisions&firma_id=$firma_id" );

        $subject = sprintf( '[GOOIDA] Yeni Revizyon: %s', $firma->firma_adi );
        
        $message = sprintf(
            "Yeni bir revizyon onay bekleniyor.\n\n" .
            "Firma: %s\n" .
            "Müşteri Email: %s\n" .
            "Gönderilen Zaman: %s\n\n" .
            "Detayları Görüntüle: %s",
            $firma->firma_adi,
            $firma->email,
            current_time( 'mysql' ),
            $firma_link
        );

        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Notify customer revision approved
     * 
     * Müşteriye revizyon onayı bildirimini gönder
     *
     * @param int $firma_id Firma ID
     * @param object $firma Firma nesnesi
     */
    private static function notify_customer_revision_approved( int $firma_id, $firma ): void {
        $subject = '[GOOIDA] Düzenlemeleriniz Onaylandı';
        
        $message = sprintf(
            "Sayın,\n\n" .
            "Firmanız '%s' için yaptığınız düzenlemeler onaylanmış ve yayında.\n\n" .
            "Teşekkürler.",
            $firma->firma_adi
        );

        wp_mail( $firma->email, $subject, $message );
    }

    /**
     * Notify customer revision rejected
     * 
     * Müşteriye revizyon reddi bildirimini gönder
     *
     * @param int $firma_id Firma ID
     * @param object $firma Firma nesnesi
     * @param string $reason Reddedilme nedeni
     */
    private static function notify_customer_revision_rejected( int $firma_id, $firma, string $reason = '' ): void {
        $subject = '[GOOIDA] Düzenlemeleriniz Gözden Geçirildi';
        
        $message = sprintf(
            "Sayın,\n\n" .
            "Firmanız '%s' için yaptığınız düzenlemeler gözden geçirilmiştir.\n\n" .
            "Reddetme Nedeni:\n%s\n\n" .
            "Lütfen tekrar deneyin.",
            $firma->firma_adi,
            $reason ?: 'Belirtilmemiş'
        );

        wp_mail( $firma->email, $subject, $message );
    }
}
