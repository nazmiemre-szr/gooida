<?php
// GOOIDA Theme: header.php
// Tamamen sade, renk değişebilir, dinamik CSS ile şekillenecek nav bar
if (!defined('ABSPATH')) exit;

// Tema ayarlarını oku (örn: JSON dosyası)
$settings_path = __DIR__ . '/theme-settings.json';
$settings = [
    'header_bg' => '#f6f7f9',
    'header_fg' => '#222',
    'logo_url' => '',
    'menu_css' => '',
];
if (file_exists($settings_path)) {
    $settings = array_merge($settings, json_decode(file_get_contents($settings_path), true) ?: []);
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php wp_head(); ?>
    <?php // Son aktif stile injection (dinamik isimli dosya)
    $css_dir = __DIR__ . '/dynamic/';
    if (is_dir($css_dir)) {
        $files = glob($css_dir . 'style_*.css');
        if ($files) {
            usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
            $last = basename($files[0]);
            echo '<link rel="stylesheet" href="' . get_stylesheet_directory_uri() . '/dynamic/' . esc_attr($last) . '?v=' . filemtime($files[0]) . '" />';
        }
    } ?>
</head>
<body <?php body_class(); ?>>
<header style="background:<?php echo esc_attr($settings['header_bg']); ?>;color:<?php echo esc_attr($settings['header_fg']); ?>;margin:0;padding:0 0 1px;box-shadow:0 2px 10px #aab0bb18;">
    <div style="display:flex;align-items:center;max-width:1040px;margin:0 auto;padding:16px 8px 10px;">
        <a href="/" style="text-decoration:none"><img src="<?php echo esc_url($settings['logo_url']) ?: get_stylesheet_directory_uri().'/logo.svg'; ?>" alt="GOOIDA" style="height:38px;margin-right:20px;"></a>
        <nav id="gooida-nav" style="flex:1;">
            <?php
            // Kendi basit nav menü (panelden yazılabilir HTML)
            echo !empty($settings['nav_html']) ? $settings['nav_html'] : '<ul style="display:flex;gap:30px;list-style:none;margin:0;padding:0;font-size:17px"><li><a href="/">Anasayfa</a></li><li><a href="/firmalar">Firmalar</a></li><li><a href="/ilanlar">İlanlar</a></li><li><a href="/iletisim">İletişim</a></li></ul>';
            ?>
        </nav>
    </div>
</header>