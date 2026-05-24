<?php
// GOOIDA Tema Fonksiyonları
if (!defined('ABSPATH')) exit;

// Tema destekleri
default:
add_theme_support('title-tag');
add_theme_support('post-thumbnails');
add_theme_support('custom-logo');

// Ana stil
action('wp_enqueue_scripts', function() {
    wp_enqueue_style('gooida-theme', get_stylesheet_directory_uri() . '/style.css', [], '1.0.0');
});

// VIP Premium Firma Shortcode
action('init', function() {
    add_shortcode('premium_firmalar', function() {
        if (!function_exists('gooida_get_premium_firmalar')) return '';
        $cards = gooida_get_premium_firmalar();
        if (empty($cards)) return '<div>Hiçbir premium firma mevcut değil.</div>';
        $out = '<div class="gooida-premium-cards">';
        foreach($cards as $firma) {
            $out .= '<div class="gooida-card premium"><span class="badge">PREMIUM</span>';
            $out .= '<h3>' . esc_html($firma->firma_adi) . '</h3>';
            $out .= '<div class="desc">' . esc_html(mb_substr(strip_tags($firma->aciklama ?? ''),0,80)) . '</div>';
            $out .= '<a href="' . esc_url($firma->firma_url) . '" class="btn">Detay</a>';
            $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
    });
});

// Örnek veri sağlayıcı (devamında işletme veritabanınızdan çekebilirsiniz)
if (!function_exists('gooida_get_premium_firmalar')) {
function gooida_get_premium_firmalar() {
    // Bu örnek, süper basit array. Gerçekte firmalar DB'den gelecek
    return [
        (object)[ 'firma_adi'=>'Hotel Elegance', 'aciklama'=>'Denize sıfır premium tatil yeri.', 'firma_url'=>'/firma/hotel-elegance' ],
        (object)[ 'firma_adi'=>'Villa Life', 'aciklama'=>'Ultra lüks ve izole tatil konsepti.', 'firma_url'=>'/firma/villa-life' ]
    ];
}
}
