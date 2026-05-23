<?php
/**
 * Main Plugin Class
 * 
 * @package GOOIDA
 * @subpackage Plugin
 * @since 1.0.0
 */

namespace GOOIDA;

class Plugin {
    /**
     * Plugin instance
     */
    private static ?Plugin $instance = null;

    /**
     * Get plugin instance (Singleton)
     */
    public static function get_instance(): Plugin {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize plugin
     */
    public static function init(): void {
        $instance = self::get_instance();
        $instance->setup_hooks();
    }

    /**
     * Plugin activation
     */
    public static function activate(): void {
        // Veritabanı tablolarını oluştur
        Database::create_tables();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate(): void {
        // Cleanup here if needed
    }

    /**
     * Setup plugin hooks
     */
    private function setup_hooks(): void {
        // Admin hooks
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

        // Frontend hooks
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );

        // Custom post types ve taxonomies
        add_action( 'init', [ $this, 'register_post_types' ] );
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu(): void {
        add_menu_page(
            'GOOIDA Platform',
            'GOOIDA',
            'manage_options',
            'gooida-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-admin-home',
            50
        );
    }

    /**
     * Render dashboard
     */
    public function render_dashboard(): void {
        echo '<div class="wrap">';
        echo '<h1>GOOIDA Platform Dashboard</h1>';
        echo '<p>Hoş geldiniz! GOOIDA platformu başarıyla kurulmuştur.</p>';
        echo '</div>';
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts(): void {
        // Admin CSS ve JS
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts(): void {
        // Frontend CSS ve JS
    }

    /**
     * Register custom post types
     */
    public function register_post_types(): void {
        // Custom post types gerekirse burada tanımla
    }
}
