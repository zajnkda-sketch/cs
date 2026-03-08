<?php
/**
 * 文章详情页模板
 * @package ChengduLife
 */
get_header();
?>
<main id="primary" class="site-main">
    <div class="container">
        <div class="single-layout">

            <!-- 文章主体 -->
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'single-article' ); ?>>
                <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

                <!-- 面包屑 -->
                <nav class="breadcrumb" style="font-size:13px;color:var(--color-text-muted);margin-bottom:20px;">
                    <a href="<?php echo esc_url( home_url() ); ?>">首页</a>
                    <?php
                    $cat = cdl_get_primary_category();
                    if ( $cat ) {
                        echo ' / <a href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
                    }
                    ?>
                    / <?php echo mb_substr( get_the_title(), 0, 20 ); ?>
                </nav>

                <!-- 文章头部 -->
                <header class="article-header">
                    <?php if ( $cat ) : ?>
                    <a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"
                       class="tag <?php echo esc_attr( cdl_get_category_class( $cat->slug ) ); ?>"
                       style="margin-bottom:12px;display:inline-block;">
                        <?php echo esc_html( $cat->name ); ?>
                    </a>
                    <?php endif; ?>
                    <h1 class="article-title"><?php the_title(); ?></h1>
                    <div class="article-meta">
                        <span><i class="far fa-user"></i> <?php the_author(); ?></span>
                        <span><i class="far fa-clock"></i> <?php echo cdl_human_time_diff(); ?></span>
                        <span><i class="far fa-eye"></i> <?php echo cdl_reading_time(); ?></span>
                        <?php
                        $views = (int) get_post_meta( get_the_ID(), 'post_views_count', true );
                        if ( $views ) echo '<span><i class="fas fa-chart-bar"></i> ' . number_format( $views ) . ' 次阅读</span>';
                        ?>
                    </div>
                </header>

                <!-- 特色图片 -->
                <?php if ( has_post_thumbnail() ) : ?>
                <div class="article-thumbnail" style="margin-bottom:28px;">
                    <?php the_post_thumbnail( 'cdl-wide', [ 'style' => 'width:100%;border-radius:12px;' ] ); ?>
                </div>
                <?php endif; ?>

                <!-- 正文 -->
                <div class="article-content">
                    <?php the_content(); ?>
                </div>

                <!-- 标签 -->
                <?php
                $tags = get_the_tags();
                if ( $tags ) :
                ?>
                <div class="article-tags" style="margin-top:28px;display:flex;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:13px;color:var(--color-text-muted);">标签：</span>
                    <?php foreach ( $tags as $tag ) : ?>
                    <a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>"
                       class="tag tag-news"><?php echo esc_html( $tag->name ); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- 上下篇 -->
                <nav class="post-navigation" style="display:flex;gap:16px;margin-top:32px;">
                    <?php
                    $prev = get_previous_post();
                    $next = get_next_post();
                    if ( $prev ) echo '<a href="' . esc_url( get_permalink( $prev ) ) . '" class="btn btn-outline" style="flex:1;font-size:14px;"><i class="fas fa-chevron-left"></i> ' . mb_substr( $prev->post_title, 0, 20 ) . '</a>';
                    if ( $next ) echo '<a href="' . esc_url( get_permalink( $next ) ) . '" class="btn btn-outline" style="flex:1;font-size:14px;">' . mb_substr( $next->post_title, 0, 20 ) . ' <i class="fas fa-chevron-right"></i></a>';
                    ?>
                </nav>

                <!-- 评论 -->
                <?php if ( comments_open() || get_comments_number() ) : ?>
                <div style="margin-top:40px;">
                    <?php comments_template(); ?>
                </div>
                <?php endif; ?>

                <?php endwhile; endif; ?>
            </article>

            <!-- 侧边栏 -->
            <aside class="home-sidebar-col">
                <?php get_template_part( 'template-parts/sidebar', 'home' ); ?>
            </aside>

        </div>
    </div>
</main>
<?php get_footer(); ?>
