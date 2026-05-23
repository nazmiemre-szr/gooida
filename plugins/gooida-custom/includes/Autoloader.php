<?php
/**
 * PSR-4 Autoloader for GOOIDA Plugin
 * 
 * @package GOOIDA
 * @subpackage Autoloader
 * @since 1.0.0
 */

namespace GOOIDA;

class Autoloader {
    /**
     * Namespace prefix
     */
    private static string $namespace = 'GOOIDA';

    /**
     * Base directory
     */
    private static string $base_dir;

    /**
     * Register the autoloader
     */
    public static function register(): void {
        self::$base_dir = dirname( __DIR__ ) . DIRECTORY_SEPARATOR;
        spl_autoload_register( [ self::class, 'load' ] );
    }

    /**
     * Load a class
     *
     * @param string $class Full qualified class name
     */
    public static function load( string $class ): void {
        // Check if class starts with our namespace
        $prefix = self::$namespace . '\\';
        if ( strpos( $class, $prefix ) !== 0 ) {
            return;
        }

        // Remove namespace prefix
        $relative_class = substr( $class, strlen( $prefix ) );

        // Convert to file path
        $file = self::$base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

        // Include file if exists
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
