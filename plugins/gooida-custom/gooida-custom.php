<?php
/**
 * Plugin Name: GOOIDA Custom Platform
 * Plugin URI: https://github.com/nazmiemre-szr/gooida
 * Description: Zero-Bloat WordPress Platform with Custom Database Architecture
 * Version: 1.0.0
 * Author: GOOIDA Team
 * Author URI: https://github.com/nazmiemre-szr
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: gooida-custom
 * Domain Path: /languages
 * Requires at least: 7.0
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GOOIDA_PLUGIN_FILE', __FILE__ );
define( 'GOOIDA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GOOIDA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GOOIDA_VERSION', '1.0.0' );

// Autoloader
require_once GOOIDA_PLUGIN_DIR . 'includes/Autoloader.php';

use GOOIDA\Autoloader;
use GOOIDA\Plugin;

Autoloader::register();

// Plugin aktivasyonu
register_activation_hook( GOOIDA_PLUGIN_FILE, [ Plugin::class, 'activate' ] );

// Plugin deaktivasyonu
register_deactivation_hook( GOOIDA_PLUGIN_FILE, [ Plugin::class, 'deactivate' ] );

// Plugin başlatılması
add_action( 'plugins_loaded', [ Plugin::class, 'init' ] );
