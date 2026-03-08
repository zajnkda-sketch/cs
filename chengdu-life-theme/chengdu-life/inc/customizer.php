<?php
/**
 * 主题定制器设置
 * @package ChengduLife
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function cdl_customize_register( $wp_customize ) {

    /* ---- 网站基本信息 ---- */
    $wp_customize->add_section( 'cdl_site_info', [
        'title'    => '网站基本信息',
        'priority' => 30,
    ] );

    $wp_customize->add_setting( 'cdl_icp', [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'cdl_icp', [
        'label'   => 'ICP 备案号',
        'section' => 'cdl_site_info',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'cdl_footer_text', [ 'default' => '', 'sanitize_callback' => 'wp_kses_post' ] );
    $wp_customize->add_control( 'cdl_footer_text', [
        'label'   => '页脚自定义文字',
        'section' => 'cdl_site_info',
        'type'    => 'textarea',
    ] );

    /* ---- 首页设置 ---- */
    $wp_customize->add_section( 'cdl_homepage', [
        'title'    => '首页设置',
        'priority' => 35,
    ] );

    $wp_customize->add_setting( 'cdl_hero_title', [
        'default'           => '搜索成都任何资讯...',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'cdl_hero_title', [
        'label'   => '搜索框占位文字',
        'section' => 'cdl_homepage',
        'type'    => 'text',
    ] );

    /* ---- 颜色设置 ---- */
    $wp_customize->add_section( 'cdl_colors', [
        'title'    => '主题颜色',
        'priority' => 40,
    ] );

    $wp_customize->add_setting( 'cdl_color_primary_start', [
        'default'           => '#FF8C00',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'cdl_color_primary_start', [
        'label'   => '主色调（起始）',
        'section' => 'cdl_colors',
    ] ) );

    $wp_customize->add_setting( 'cdl_color_primary_end', [
        'default'           => '#FF5252',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'cdl_color_primary_end', [
        'label'   => '主色调（结束）',
        'section' => 'cdl_colors',
    ] ) );
}
add_action( 'customize_register', 'cdl_customize_register' );

/**
 * 将定制器颜色输出为 CSS 变量
 */
function cdl_customizer_css() {
    $start = get_theme_mod( 'cdl_color_primary_start', '#FF8C00' );
    $end   = get_theme_mod( 'cdl_color_primary_end',   '#FF5252' );
    echo '<style>:root{--color-primary-start:' . esc_attr( $start ) . ';--color-primary-end:' . esc_attr( $end ) . ';}</style>';
}
add_action( 'wp_head', 'cdl_customizer_css' );
