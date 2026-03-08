<?php
/**
 * 成都生活圈主题 - 核心功能函数
 *
 * @package ChengduLife
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CDL_VERSION',   '1.0.0' );
define( 'CDL_DIR',       get_template_directory() );
define( 'CDL_URI',       get_template_directory_uri() );
define( 'CDL_ASSETS',    CDL_URI . '/assets' );

/* ============================================================
   主题支持
   ============================================================ */
function cdl_setup() {
    load_theme_textdomain( 'chengdu-life', CDL_DIR . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
    add_theme_support( 'custom-logo', [
        'height'      => 50,
        'width'       => 160,
        'flex-width'  => true,
        'flex-height' => true,
    ] );
    add_theme_support( 'customize-selective-refresh-widgets' );
    add_theme_support( 'responsive-embeds' );

    // 文章缩略图尺寸
    set_post_thumbnail_size( 800, 450, true );
    add_image_size( 'cdl-card',  400, 240, true );
    add_image_size( 'cdl-wide',  800, 400, true );
    add_image_size( 'cdl-thumb', 120, 90,  true );

    // 导航菜单
    register_nav_menus( [
        'primary' => __( '主导航', 'chengdu-life' ),
        'footer'  => __( '页脚导航', 'chengdu-life' ),
    ] );
}
add_action( 'after_setup_theme', 'cdl_setup' );

/* ============================================================
   内容宽度
   ============================================================ */
function cdl_content_width() {
    $GLOBALS['content_width'] = 860;
}
add_action( 'after_setup_theme', 'cdl_content_width', 0 );

/* ============================================================
   注册样式 & 脚本
   ============================================================ */
function cdl_enqueue_assets() {
    // Google Fonts
    wp_enqueue_style(
        'cdl-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap',
        [],
        null
    );

    // 主样式
    wp_enqueue_style(
        'chengdu-life',
        get_stylesheet_uri(),
        [ 'cdl-fonts' ],
        CDL_VERSION
    );

    // 主题额外样式
    wp_enqueue_style(
        'cdl-main',
        CDL_ASSETS . '/css/main.css',
        [ 'chengdu-life' ],
        CDL_VERSION
    );

    // Font Awesome
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        [],
        '6.4.0'
    );

    // 主脚本
    wp_enqueue_script(
        'cdl-main',
        CDL_ASSETS . '/js/main.js',
        [ 'jquery' ],
        CDL_VERSION,
        true
    );

    // 向前端注入 AJAX 参数
    wp_localize_script( 'cdl-main', 'cdlData', [
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'cdl_nonce' ),
        'siteUrl'   => get_site_url(),
        'themeUrl'  => CDL_URI,
    ] );

    // 单篇文章：评论脚本
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'cdl_enqueue_assets' );

/* ============================================================
   注册侧边栏 / 小工具区域
   ============================================================ */
function cdl_register_sidebars() {
    $defaults = [
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title section-title">',
        'after_title'   => '</h3>',
    ];

    register_sidebar( array_merge( $defaults, [
        'name' => __( '主侧边栏', 'chengdu-life' ),
        'id'   => 'sidebar-main',
    ] ) );

    register_sidebar( array_merge( $defaults, [
        'name' => __( '首页右侧栏', 'chengdu-life' ),
        'id'   => 'sidebar-home',
    ] ) );

    register_sidebar( array_merge( $defaults, [
        'name' => __( '页脚第一列', 'chengdu-life' ),
        'id'   => 'footer-1',
    ] ) );

    register_sidebar( array_merge( $defaults, [
        'name' => __( '页脚第二列', 'chengdu-life' ),
        'id'   => 'footer-2',
    ] ) );

    register_sidebar( array_merge( $defaults, [
        'name' => __( '页脚第三列', 'chengdu-life' ),
        'id'   => 'footer-3',
    ] ) );
}
add_action( 'widgets_init', 'cdl_register_sidebars' );

/* ============================================================
   注册自定义文章类型
   ============================================================ */
function cdl_register_post_types() {

    // 办事指南
    register_post_type( 'cd_guide', [
        'labels'       => [
            'name'          => '办事指南',
            'singular_name' => '指南',
            'add_new_item'  => '添加新指南',
            'edit_item'     => '编辑指南',
            'menu_name'     => '办事指南',
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-book',
        'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
        'rewrite'      => [ 'slug' => 'guide' ],
        'show_in_rest' => true,
    ] );

    // 活动信息
    register_post_type( 'cd_event', [
        'labels'       => [
            'name'          => '活动信息',
            'singular_name' => '活动',
            'add_new_item'  => '添加新活动',
            'menu_name'     => '活动信息',
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-calendar',
        'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
        'rewrite'      => [ 'slug' => 'event' ],
        'show_in_rest' => true,
    ] );

    // 招聘信息
    register_post_type( 'cd_job', [
        'labels'       => [
            'name'          => '招聘信息',
            'singular_name' => '招聘',
            'add_new_item'  => '添加招聘信息',
            'menu_name'     => '招聘信息',
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-businessman',
        'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
        'rewrite'      => [ 'slug' => 'job' ],
        'show_in_rest' => true,
    ] );
}
add_action( 'init', 'cdl_register_post_types' );

/* ============================================================
   注册自定义分类法
   ============================================================ */
function cdl_register_taxonomies() {
    // 资讯分类
    register_taxonomy( 'cd_category', 'post', [
        'labels'            => [
            'name'          => '资讯分类',
            'singular_name' => '分类',
            'menu_name'     => '资讯分类',
        ],
        'hierarchical'      => true,
        'public'            => true,
        'show_in_rest'      => true,
        'rewrite'           => [ 'slug' => 'cat' ],
    ] );
}
add_action( 'init', 'cdl_register_taxonomies' );

/* ============================================================
   辅助函数
   ============================================================ */

/**
 * 获取文章分类对应的颜色 class
 */
function cdl_get_category_class( $cat_slug = '' ) {
    $map = [
        'traffic'  => 'tag-traffic',
        'social'   => 'tag-social',
        'gov'      => 'tag-gov',
        'edu'      => 'tag-edu',
        'food'     => 'tag-food',
        'travel'   => 'tag-travel',
        'life'     => 'tag-life',
    ];
    return $map[ $cat_slug ] ?? 'tag-news';
}

/**
 * 获取文章分类对应的分类卡片颜色 class
 */
function cdl_get_category_card_class( $cat_slug = '' ) {
    $map = [
        'traffic'  => 'cat-card-traffic',
        'social'   => 'cat-card-social',
        'gov'      => 'cat-card-gov',
        'edu'      => 'cat-card-edu',
        'food'     => 'cat-card-food',
        'travel'   => 'cat-card-travel',
        'life'     => 'cat-card-life',
    ];
    return $map[ $cat_slug ] ?? 'cat-card-news';
}

/**
 * 获取文章阅读时间估算（分钟）
 */
function cdl_reading_time( $post_id = null ) {
    $content    = get_post_field( 'post_content', $post_id );
    $word_count = mb_strlen( strip_tags( $content ) );
    $minutes    = max( 1, ceil( $word_count / 400 ) );
    return $minutes . ' 分钟阅读';
}

/**
 * 获取人性化时间差
 */
function cdl_human_time_diff( $post_id = null ) {
    $time = get_post_time( 'U', false, $post_id );
    $diff = current_time( 'timestamp' ) - $time;

    if ( $diff < 3600 )        return floor( $diff / 60 ) . ' 分钟前';
    if ( $diff < 86400 )       return floor( $diff / 3600 ) . ' 小时前';
    if ( $diff < 86400 * 7 )   return floor( $diff / 86400 ) . ' 天前';
    return get_the_date( 'Y-m-d', $post_id );
}

/**
 * 截断文字
 */
function cdl_excerpt( $text, $length = 80 ) {
    $text = strip_tags( $text );
    if ( mb_strlen( $text ) <= $length ) return $text;
    return mb_substr( $text, 0, $length ) . '...';
}

/**
 * 获取文章主分类
 */
function cdl_get_primary_category( $post_id = null ) {
    $cats = get_the_category( $post_id );
    return $cats ? $cats[0] : null;
}

/* ============================================================
   自定义摘要长度
   ============================================================ */
function cdl_excerpt_length( $length ) {
    return is_admin() ? $length : 80;
}
add_filter( 'excerpt_length', 'cdl_excerpt_length' );

function cdl_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'cdl_excerpt_more' );

/* ============================================================
   加载子模块
   ============================================================ */
require_once CDL_DIR . '/inc/customizer.php';
require_once CDL_DIR . '/inc/widgets.php';
require_once CDL_DIR . '/inc/ajax.php';
