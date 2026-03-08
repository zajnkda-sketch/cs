<?php
/**
 * 搜索结果页
 * @package ChengduLife
 */
get_header();
$keyword = get_search_query();
?>
<main id="primary" class="site-main">
    <div class="container" style="padding-top:40px;padding-bottom:60px;">

        <div class="archive-header" style="margin-bottom:32px;padding:24px 32px;background:var(--color-bg-card);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);">
            <h1 style="font-size:22px;font-weight:700;color:var(--color-text-primary);">
                搜索 "<span style="color:var(--color-primary);"><?php echo esc_html( $keyword ); ?></span>" 的结果
            </h1>
            <?php if ( have_posts() ) : ?>
            <p style="font-size:14px;color:var(--color-text-muted);margin-top:8px;">共找到 <?php echo $wp_query->found_posts; ?> 条相关结果</p>
            <?php endif; ?>
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
                <div style="text-align:center;padding:60px 0;">
                    <div style="font-size:64px;margin-bottom:16px;">🔍</div>
                    <h2 style="font-size:20px;color:var(--color-text-secondary);margin-bottom:12px;">未找到相关内容</h2>
                    <p style="color:var(--color-text-muted);margin-bottom:24px;">请尝试其他关键词，或浏览以下热门分类</p>
                    <a href="<?php echo esc_url( home_url() ); ?>" class="btn btn-primary">返回首页</a>
                </div>
                <?php endif; ?>
            </div>
            <aside class="home-sidebar-col">
                <?php get_template_part( 'template-parts/sidebar', 'home' ); ?>
            </aside>
        </div>
    </div>
</main>
<?php get_footer(); ?>
