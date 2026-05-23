<?php
/**
 * GOOIDA Configuration Constants
 * 
 * @package GOOIDA
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Database table prefixes
define( 'GOOIDA_TABLE_KATEGORILER', 'wp_gooida_kategoriler' );
define( 'GOOIDA_TABLE_FIRMALAR', 'wp_gooida_firmalar' );
define( 'GOOIDA_TABLE_FIRMALAR_RESIMLERI', 'wp_gooida_firmalar_resimleri' );
define( 'GOOIDA_TABLE_ILANLAR', 'wp_gooida_ilanlar' );
define( 'GOOIDA_TABLE_REKLAM_ALANLARI', 'wp_gooida_reklam_alanlari' );
define( 'GOOIDA_TABLE_REKLAMLAR', 'wp_gooida_reklamlar' );
define( 'GOOIDA_TABLE_ODEME_BILDIRIMLERI', 'wp_gooida_odeme_bildirimleri' );
define( 'GOOIDA_TABLE_REHBER', 'wp_gooida_rehber' );
define( 'GOOIDA_TABLE_SEZON_AYARLARI', 'wp_gooida_sezon_ayarlari' );

// Membership types
define( 'GOOIDA_MEMBERSHIP_FREE', 'free' );
define( 'GOOIDA_MEMBERSHIP_VERIFIED', 'verified' );
define( 'GOOIDA_MEMBERSHIP_PREMIUM', 'premium' );

// Image settings
define( 'GOOIDA_IMAGE_SIZE', 700 );
define( 'GOOIDA_IMAGE_FORMAT', 'jpg' );
define( 'GOOIDA_IMAGE_QUALITY', 85 );
define( 'GOOIDA_IMAGE_MAX_SIZE', 5 * MB_IN_BYTES );

// Image limits by membership
define( 'GOOIDA_FREE_MAX_IMAGES', 0 );
define( 'GOOIDA_VERIFIED_MAX_IMAGES', 3 );
define( 'GOOIDA_PREMIUM_MAX_IMAGES', 30 );

// Update lock (7 days)
define( 'GOOIDA_UPDATE_LOCK_DAYS', 7 );
define( 'GOOIDA_UPDATE_LOCK_SECONDS', 7 * DAY_IN_SECONDS );

// Seasons
define( 'GOOIDA_SEASON_SUMMER', 'yaz' );
define( 'GOOIDA_SEASON_WINTER', 'kis' );

// Ad status
define( 'GOOIDA_AD_STATUS_PENDING', 'beklemede' );
define( 'GOOIDA_AD_STATUS_APPROVED', 'onaylandi' );
define( 'GOOIDA_AD_STATUS_CANCELLED', 'iptal' );

// Payment types
define( 'GOOIDA_PAYMENT_TYPE_TRANSFER', 'manuel_havale' );
define( 'GOOIDA_PAYMENT_TYPE_COD', 'kapida_odeme' );
