<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF8C00">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">

<!-- ========== 顶部导航栏 ========== -->
<header id="masthead" class="site-header">
    <div class="header-inner container">

        <!-- Logo -->
        <div class="site-branding">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo-text" rel="home">
                    <span class="logo-icon">🐼</span>
                    <span class="logo-name"><?php bloginfo( 'name' ); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <!-- 主导航 -->
        <nav id="site-navigation" class="main-navigation" aria-label="主导航">
            <?php
            wp_nav_menu( [
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'menu_class'     => 'nav-menu',
                'container'      => false,
                'fallback_cb'    => 'cdl_fallback_menu',
            ] );
            ?>
        </nav>

        <!-- 右侧工具栏 -->
        <div class="header-tools">
            <!-- 搜索触发 -->
            <button class="header-search-btn" aria-label="搜索" id="headerSearchToggle">
                <i class="fas fa-search"></i>
            </button>
            <!-- 移动端菜单 -->
            <button class="mobile-menu-btn" aria-label="菜单" id="mobileMenuToggle">
                <span></span><span></span><span></span>
            </button>
        </div>

    </div><!-- .header-inner -->

    <!-- 搜索浮层 -->
    <div class="header-search-overlay" id="headerSearchOverlay">
        <div class="container">
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="header-search-form">
                <input type="search" name="s" placeholder="搜索成都任何资讯..." value="<?php echo get_search_query(); ?>" autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i></button>
                <button type="button" class="search-close" id="searchClose"><i class="fas fa-times"></i></button>
            </form>
            <!-- 热门搜索词 -->
            <div class="hot-searches">
                <span class="hot-label">热门：</span>
                <a href="<?php echo esc_url( home_url( '/?s=限行' ) ); ?>">限行查询</a>
                <a href="<?php echo esc_url( home_url( '/?s=五险一金' ) ); ?>">五险一金</a>
                <a href="<?php echo esc_url( home_url( '/?s=成都地铁' ) ); ?>">成都地铁</a>
                <a href="<?php echo esc_url( home_url( '/?s=大熊猫' ) ); ?>">大熊猫</a>
                <a href="<?php echo esc_url( home_url( '/?s=天府新区' ) ); ?>">天府新区</a>
            </div>
        </div>
    </div>

</header><!-- #masthead -->

<!-- 移动端侧滑菜单 -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
<div class="mobile-menu-drawer" id="mobileMenuDrawer">
    <div class="mobile-menu-header">
        <span class="logo-icon">🐼</span>
        <span><?php bloginfo( 'name' ); ?></span>
        <button id="mobileMenuClose"><i class="fas fa-times"></i></button>
    </div>
    <?php
    wp_nav_menu( [
        'theme_location' => 'primary',
        'menu_id'        => 'mobile-menu',
        'menu_class'     => 'mobile-nav-menu',
        'container'      => false,
        'fallback_cb'    => 'cdl_fallback_menu',
    ] );
    ?>
</div>

<div id="content" class="site-content">
<?php
/**
 * 默认菜单回退
 */
function cdl_fallback_menu() {
    $items = [
        '首页'     => home_url( '/' ),
        '社区'     => home_url( '/community' ),
        '发现'     => home_url( '/discover' ),
        '热门活动' => home_url( '/event' ),
        '生活指南' => home_url( '/guide' ),
        '关于我们' => home_url( '/about' ),
    ];
    echo '<ul class="nav-menu">';
    foreach ( $items as $label => $url ) {
        echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo '</ul>';
}
