<?php
/**
 * Fertilizer Price Platform — 主题功能配置
 *
 * 主要职责：
 *  1. 注册并加载主题所需的 CSS / JS
 *  2. 向前端注入 AJAX URL 和 nonce（供 main.js 使用）
 *  3. 注册 WordPress 核心功能支持
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================
// 1. 主题基础支持
// ============================================================

add_action( 'after_setup_theme', 'fertp_theme_setup' );

function fertp_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
    add_theme_support( 'automatic-feed-links' );

    register_nav_menus( [
        'primary' => '主导航',
        'footer'  => '页脚导航',
    ] );
}

// ============================================================
// 2. 注册并加载脚本 / 样式
// ============================================================

add_action( 'wp_enqueue_scripts', 'fertp_theme_enqueue' );

function fertp_theme_enqueue() {
    $theme_version = wp_get_theme()->get( 'Version' ) ?: '1.0.0';
    $theme_uri     = get_template_directory_uri();

    // Bootstrap 5 CSS
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
        [],
        '5.3.0'
    );

    // Bootstrap Icons
    wp_enqueue_style(
        'bootstrap-icons',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
        [],
        '1.10.5'
    );

    // 主题样式
    wp_enqueue_style(
        'fertilizer-theme',
        get_stylesheet_uri(),
        [ 'bootstrap' ],
        $theme_version
    );

    // jQuery（WordPress 内置）
    wp_enqueue_script( 'jquery' );

    // Bootstrap 5 JS
    wp_enqueue_script(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
        [],
        '5.3.0',
        true
    );

    // Chart.js
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        [],
        '4.4.0',
        true
    );

    // 主题 main.js
    wp_enqueue_script(
        'fertilizer-main',
        $theme_uri . '/js/main.js',
        [ 'jquery', 'chartjs' ],
        $theme_version,
        true
    );

    // 向 main.js 注入 AJAX 配置
    wp_localize_script( 'fertilizer-main', 'fertilizer_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'fertilizer_nonce' ),
    ] );
}

// ============================================================
// 3. 内容宽度
// ============================================================

if ( ! isset( $content_width ) ) {
    $content_width = 1200;
}
