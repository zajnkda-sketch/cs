<?php
/**
 * 归档/分类页模板
 * @package ChengduLife
 */
get_header();
?>
<main id="primary" class="site-main">
    <div class="container" style="padding-top:40px;padding-bottom:60px;">

        <!-- 分类头部 -->
        <div class="archive-header" style="margin-bottom:32px;padding:28px 32px;background:linear-gradient(135deg,#FF8C00,#FF5252);border-radius:var(--radius-md);color:#fff;">
            <h1 style="font-size:28px;font-weight:700;margin-bottom:8px;"><?php the_archive_title(); ?></h1>
            <?php the_archive_description( '<p style="opacity:0.85;font-size:15px;">', '</p>' ); ?>
        </div>

        <div class="home-content-grid">
            <div class="home-main-col">
                <?php if ( have_posts() ) : ?>
                <div class="post-cards-grid">
                    <?php while ( have_posts() ) : the_post(); ?>
                    <?php get_template_part( 'template-parts/content', 'card' ); ?>
                    <?php endwhile; ?>
                </div>
                <div class="pagination">
                    <?php echo paginate_links( [ 'prev_text' => '«', 'next_text' => '»' ] ); ?>
                </div>
                <?php else : ?>
                <p style="color:var(--color-text-muted);">该分类下暂无文章。</p>
                <?php endif; ?>
            </div>
            <aside class="home-sidebar-col">
                <?php get_template_part( 'template-parts/sidebar', 'home' ); ?>
            </aside>
        </div>
    </div>
</main>
<?php get_footer(); ?>
