<?php
// Ana Sayfa (GOOIDA)
get_header();
?>
<main style="max-width:820px;margin:40px auto 0;">
    <h1>Gooida – Akıllı Bölge Platformu</h1>
    <p>Wordpress bağımsız, programatik SEO’lu, sıfır şişkinlikte bölge platformu.</p>

    <section>
        <h2>🌟 Premium İşletmeler</h2>
        <?php echo do_shortcode('[premium_firmalar]'); ?>
    </section>
</main>
<?php
get_footer();
