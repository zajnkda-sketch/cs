<?php
/**
 * 主循环模板（备用）
 * @package ChengduLife
 */
get_header();
?>
<main id="primary" class="site-main">
    <div class="container" style="padding-top:40px;padding-bottom:60px;">
        <div class="home-content-grid">
            <div class="home-main-col">
                <h1 class="section-title">最新文章</h1>
                <?php if ( have_posts() ) : ?>
                <div class="post-cards-grid">
                    <?php while ( have_posts() ) : the_post(); ?>
                    <?php get_template_part( 'template-parts/content', 'card' ); ?>
                    <?php endwhile; ?>
                </div>
                <div class="pagination">
                    <?php
                    echo paginate_links( [
                        'prev_text' => '<i class="fas fa-chevron-left"></i>',
                        'next_text' => '<i class="fas fa-chevron-right"></i>',
                    ] );
                    ?>
                </div>
                <?php else : ?>
                <p class="no-posts">暂无文章。</p>
                <?php endif; ?>
            </div>
            <aside class="home-sidebar-col">
                <?php get_template_part( 'template-parts/sidebar', 'home' ); ?>
            </aside>
        </div>
    </div>
</main>
<?php get_footer(); ?>
