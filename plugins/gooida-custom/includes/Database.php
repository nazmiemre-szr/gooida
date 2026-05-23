<?php
/**
 * Database Helper Class
 * 
 * @package GOOIDA
 * @subpackage Database
 * @since 1.0.0
 */

namespace GOOIDA;

class Database {
    /**
     * $wpdb global
     */
    private static $wpdb;

    /**
     * Initialize
     */
    public static function init(): void {
        global $wpdb;
        self::$wpdb = $wpdb;
    }

    /**
     * Create all tables
     */
    public static function create_tables(): void {
        self::init();

        $charset_collate = self::$wpdb->get_charset_collate();

        // Read schema file
        $schema_file = dirname( dirname( dirname( __DIR__ ) ) ) . '/database/schema.sql';
        if ( file_exists( $schema_file ) ) {
            $schema = file_get_contents( $schema_file );
            self::execute_schema( $schema );
        }
    }

    /**
     * Execute schema SQL
     *
     * @param string $schema SQL schema
     */
    private static function execute_schema( string $schema ): void {
        $statements = array_filter( array_map( 'trim', explode( ';', $schema ) ) );
        
        foreach ( $statements as $statement ) {
            if ( ! empty( $statement ) && strpos( $statement, '--' ) !== 0 ) {
                self::$wpdb->query( $statement );
            }
        }
    }

    /**
     * Insert firma
     *
     * @param array $data Firma verisi
     * @return int|false Inserted ID veya false
     */
    public static function insert_firma( array $data ) {
        self::init();
        return self::$wpdb->insert( self::$wpdb->prefix . 'gooida_firmalar', $data );
    }

    /**
     * Get firma by ID
     *
     * @param int $firma_id Firma ID
     * @return object|null Firma nesnesi
     */
    public static function get_firma( int $firma_id ) {
        self::init();
        return self::$wpdb->get_row(
            self::$wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gooida_firmalar WHERE id = %d",
                $firma_id
            )
        );
    }

    /**
     * Get all firmalar
     *
     * @param array $args Query argümanları
     * @return array Firmalar
     */
    public static function get_firmalar( array $args = [] ) {
        self::init();

        $defaults = [
            'yayinda' => true,
            'limit' => 20,
            'offset' => 0,
            'order_by' => 'sira_numarasi',
            'order' => 'ASC',
        ];

        $args = wp_parse_args( $args, $defaults );

        $query = "SELECT * FROM {$wpdb->prefix}gooida_firmalar WHERE 1=1";

        if ( isset( $args['yayinda'] ) ) {
            $query .= self::$wpdb->prepare( " AND yayinda = %d", (int) $args['yayinda'] );
        }

        $query .= " ORDER BY {$args['order_by']} {$args['order']}";
        $query .= self::$wpdb->prepare( " LIMIT %d OFFSET %d", $args['limit'], $args['offset'] );

        return self::$wpdb->get_results( $query );
    }

    /**
     * Update firma
     *
     * @param int $firma_id Firma ID
     * @param array $data Update verisi
     * @return bool Success
     */
    public static function update_firma( int $firma_id, array $data ): bool {
        self::init();
        return (bool) self::$wpdb->update(
            self::$wpdb->prefix . 'gooida_firmalar',
            $data,
            [ 'id' => $firma_id ]
        );
    }
}
