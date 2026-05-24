<?php
// Firma Detay Sayfası
get_header();
global $post;
$firma = get_post(); // Veya özel firma DB’den çekin
?>
<main style="max-width:780px; margin:40px auto; background:#fff;padding:32px 36px 18px;border-radius:14px;box-shadow:0 4px 22px #dfdfdf66;">
    <h1><?php echo esc_html($firma->post_title); ?></h1>
    <div style="margin:20px 0 10px;"><b>Üyelik:</b> <span class="badge badge-premium">PREMIUM</span></div>
    <div><?php echo apply_filters('the_content', $firma->post_content); ?></div>
    <div><a href="/" class="btn">← Tüm Firmalar</a></div>
</main>
<?php
get_footer();
