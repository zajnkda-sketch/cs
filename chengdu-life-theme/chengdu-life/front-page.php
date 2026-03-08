<?php
/**
 * 首页模板
 *
 * @package ChengduLife
 */
get_header();
?>

<main id="primary" class="site-main">

    <!-- ========== Hero 搜索区 ========== -->
    <section class="hero-section">
        <div class="container">
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="hero-search-form">
                <div class="hero-search-wrap">
                    <i class="fas fa-search hero-search-icon"></i>
                    <input type="search" name="s" class="hero-search-input"
                           placeholder="搜索成都任何资讯..."
                           value="<?php echo get_search_query(); ?>"
                           autocomplete="off">
                    <button type="submit" class="hero-search-btn">搜索</button>
                </div>
            </form>
            <!-- 热门标签 -->
            <div class="hero-tags">
                <?php
                $hot_tags = [
                    [ 'label' => '限行查询', 'url' => home_url( '/traffic-limit' ), 'class' => 'tag-traffic' ],
                    [ 'label' => '五险一金', 'url' => home_url( '/social-calculator' ), 'class' => 'tag-social' ],
                    [ 'label' => '办事指南', 'url' => home_url( '/guide' ), 'class' => 'tag-gov' ],
                    [ 'label' => '美食',     'url' => home_url( '/?cat=food' ), 'class' => 'tag-food' ],
                    [ 'label' => '旅游',     'url' => home_url( '/?cat=travel' ), 'class' => 'tag-travel' ],
                    [ 'label' => '教育',     'url' => home_url( '/?cat=edu' ), 'class' => 'tag-edu' ],
                ];
                foreach ( $hot_tags as $t ) {
                    printf(
                        '<a href="%s" class="tag %s">%s</a>',
                        esc_url( $t['url'] ),
                        esc_attr( $t['class'] ),
                        esc_html( $t['label'] )
                    );
                }
                ?>
            </div>
        </div>
    </section>

    <!-- ========== 热门分类卡片 ========== -->
    <section class="category-section">
        <div class="container">
            <h2 class="section-title">热门分类</h2>
            <div class="category-cards">
                <?php
                $categories = [
                    [ 'name' => '交通出行', 'desc' => '实时限行、路线规划', 'icon' => 'fa-car',         'class' => 'cat-card-traffic', 'url' => home_url( '/traffic-limit' ) ],
                    [ 'name' => '社保公积金', 'desc' => '查询缴纳、政策解读', 'icon' => 'fa-shield-alt', 'class' => 'cat-card-social',  'url' => home_url( '/social-calculator' ) ],
                    [ 'name' => '办事大厅', 'desc' => '政务入口、流程指引', 'icon' => 'fa-landmark',    'class' => 'cat-card-gov',     'url' => home_url( '/guide' ) ],
                    [ 'name' => '教育入学', 'desc' => '招生政策、生活指南', 'icon' => 'fa-graduation-cap','class' => 'cat-card-edu',   'url' => home_url( '/?cat=edu' ) ],
                    [ 'name' => '美食攻略', 'desc' => '火锅、串串、川菜',   'icon' => 'fa-utensils',    'class' => 'cat-card-food',    'url' => home_url( '/?cat=food' ) ],
                    [ 'name' => '旅游景点', 'desc' => '熊猫基地、宽窄巷子', 'icon' => 'fa-map-marked-alt','class' => 'cat-card-travel', 'url' => home_url( '/?cat=travel' ) ],
                ];
                foreach ( $categories as $cat ) :
                ?>
                <a href="<?php echo esc_url( $cat['url'] ); ?>" class="cat-card <?php echo esc_attr( $cat['class'] ); ?>">
                    <i class="fas <?php echo esc_attr( $cat['icon'] ); ?> cat-card-icon"></i>
                    <div class="cat-card-info">
                        <span class="cat-card-name"><?php echo esc_html( $cat['name'] ); ?></span>
                        <span class="cat-card-desc"><?php echo esc_html( $cat['desc'] ); ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========== 主内容区 ========== -->
    <section class="home-content-section">
        <div class="container">
            <div class="home-content-grid">

                <!-- 左侧：最新资讯 -->
                <div class="home-main-col">
                    <h2 class="section-title">最新资讯</h2>

                    <?php
                    $latest_query = new WP_Query( [
                        'posts_per_page' => 9,
                        'post_status'    => 'publish',
                        'ignore_sticky_posts' => true,
                    ] );
                    ?>

                    <?php if ( $latest_query->have_posts() ) : ?>

                    <!-- 置顶大图文章 -->
                    <?php $latest_query->the_post(); ?>
                    <article class="post-card post-card-featured">
                        <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                            <?php the_post_thumbnail( 'cdl-wide', [ 'class' => 'post-card-thumb' ] ); ?>
                        </a>
                        <?php endif; ?>
                        <div class="post-card-body">
                            <?php
                            $cat = cdl_get_primary_category();
                            if ( $cat ) :
                            ?>
                            <a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"
                               class="tag <?php echo esc_attr( cdl_get_category_class( $cat->slug ) ); ?>">
                                <?php echo esc_html( $cat->name ); ?>
                            </a>
                            <?php endif; ?>
                            <h3 class="post-card-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <p class="post-card-excerpt"><?php echo cdl_excerpt( get_the_excerpt(), 100 ); ?></p>
                            <div class="post-card-meta">
                                <span><i class="far fa-clock"></i> <?php echo cdl_human_time_diff(); ?></span>
                                <span><i class="far fa-eye"></i> <?php echo cdl_reading_time(); ?></span>
                            </div>
                        </div>
                    </article>

                    <!-- 普通文章网格 -->
                    <div class="post-cards-grid">
                        <?php while ( $latest_query->have_posts() ) : $latest_query->the_post(); ?>
                        <article class="post-card">
                            <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
                                <?php the_post_thumbnail( 'cdl-card', [ 'class' => 'post-card-thumb' ] ); ?>
                            </a>
                            <?php endif; ?>
                            <div class="post-card-body">
                                <?php
                                $cat = cdl_get_primary_category();
                                if ( $cat ) :
                                ?>
                                <a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"
                                   class="tag <?php echo esc_attr( cdl_get_category_class( $cat->slug ) ); ?>">
                                    <?php echo esc_html( $cat->name ); ?>
                                </a>
                                <?php endif; ?>
                                <h3 class="post-card-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <div class="post-card-meta">
                                    <span><i class="far fa-clock"></i> <?php echo cdl_human_time_diff(); ?></span>
                                    <span><i class="far fa-eye"></i> <?php echo cdl_reading_time(); ?></span>
                                </div>
                            </div>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>

                    <div class="text-center" style="margin-top:32px;">
                        <a href="<?php echo esc_url( home_url( '/news' ) ); ?>" class="btn btn-outline">查看更多资讯</a>
                    </div>

                    <?php else : ?>
                    <p class="no-posts">暂无文章，请先在后台发布内容。</p>
                    <?php endif; ?>
                </div>

                <!-- 右侧栏 -->
                <aside class="home-sidebar-col">
                    <?php get_template_part( 'template-parts/sidebar', 'home' ); ?>
                </aside>

            </div><!-- .home-content-grid -->
        </div>
    </section>

</main><!-- #primary -->

<?php get_footer(); ?>
