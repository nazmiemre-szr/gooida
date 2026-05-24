<?php
// İlan Detay Sayfası
get_header();
global $post;
$ilan = get_post(); // Veya özel ilan DB’den
?>
<main style="max-width:768px;margin:40px auto 0;background:#fff;padding:32px 32px 20px;border-radius:13px;box-shadow:0 2px 19px #dadada33;">
    <h1><?php echo esc_html($ilan->post_title); ?></h1>
    <div style="color:#777;margin-bottom:18px;"><b>Kategori:</b> Gayrimenkul</div>
    <div><?php echo apply_filters('the_content', $ilan->post_content); ?></div>
</main>
<?php
get_footer();
