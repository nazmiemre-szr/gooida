<?php
/**
 * Season Engine - Dinamik Vitrin Motoru
 * 
 * Tarih tabanlı otomatik Yaz/Kış modu geçişi
 * Mayıs-Eylül: Yaz (Konaklama, Etkinlik, Plaj)
 * Ekim-Nisan: Kış (Emlak, Kampanyalar, Esnaf)
 * 
 * @package GOOIDA
 * @subpackage SeasonEngine
 * @since 1.0.0
 */

namespace GOOIDA;

class SeasonEngine {
    /**
     * Get current season
     *
     * @return string 'yaz' or 'kis'
     */
    public static function get_current_season(): string {
        $current_month = (int) date( 'n' ); // 1-12
        
        if ( $current_month >= 5 && $current_month <= 9 ) {
            return 'yaz';
        }
        return 'kis';
    }

    /**
     * Get season dates
     *
     * @param string $season 'yaz' or 'kis'
     * @return array Array with 'start' and 'end' keys
     */
    public static function get_season_dates( string $season ): array {
        $dates = [
            'yaz' => [
                'start' => '2026-05-01',
                'end' => '2026-09-30',
            ],
            'kis' => [
                'start' => '2026-10-01',
                'end' => '2026-04-30',
            ],
        ];

        return $dates[ $season ] ?? $dates['yaz'];
    }

    /**
     * Get categories for current season
     *
     * @return array Kategori ID'leri
     */
    public static function get_season_categories(): array {
        global $wpdb;
        $season = self::get_current_season();

        $query = $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}gooida_kategoriler WHERE sezon = %s OR sezon = %s",
            $season,
            'her_sezon'
        );

        $results = $wpdb->get_col( $query );
        return array_map( 'intval', $results );
    }

    /**
     * Filter firmalar by season
     *
     * @param array $firmalar Firmalar listesi
     * @return array Filtered firmalar
     */
    public static function filter_by_season( array $firmalar ): array {
        $season_categories = self::get_season_categories();
        
        return array_filter( $firmalar, function ( $firma ) use ( $season_categories ) {
            return in_array( (int) $firma->kategori_id, $season_categories, true );
        } );
    }
}
