<?php
/**
 * 主题 AJAX 处理器
 * @package ChengduLife
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 无限滚动加载更多文章
 */
function cdl_ajax_load_more() {
    check_ajax_referer( 'cdl_nonce', 'nonce' );

    $page     = absint( $_POST['page'] ?? 1 );
    $cat      = sanitize_text_field( $_POST['cat'] ?? '' );
    $per_page = 6;

    $args = [
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
    ];
    if ( $cat ) $args['category_name'] = $cat;

    $query = new WP_Query( $args );
    $posts = [];

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $cat_obj = cdl_get_primary_category();
            $posts[] = [
                'id'        => get_the_ID(),
                'title'     => get_the_title(),
                'url'       => get_permalink(),
                'excerpt'   => cdl_excerpt( get_the_excerpt(), 80 ),
                'thumb'     => get_the_post_thumbnail_url( null, 'cdl-card' ) ?: '',
                'time'      => cdl_human_time_diff(),
                'read_time' => cdl_reading_time(),
                'cat_name'  => $cat_obj ? $cat_obj->name : '',
                'cat_slug'  => $cat_obj ? $cat_obj->slug : '',
                'cat_url'   => $cat_obj ? get_category_link( $cat_obj ) : '',
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success( [
        'posts'    => $posts,
        'has_more' => $query->max_num_pages > $page,
    ] );
}
add_action( 'wp_ajax_cdl_load_more',        'cdl_ajax_load_more' );
add_action( 'wp_ajax_nopriv_cdl_load_more', 'cdl_ajax_load_more' );

/**
 * 搜索建议（自动完成）
 */
function cdl_ajax_search_suggest() {
    check_ajax_referer( 'cdl_nonce', 'nonce' );

    $keyword = sanitize_text_field( $_GET['q'] ?? '' );
    if ( strlen( $keyword ) < 1 ) {
        wp_send_json_success( [] );
    }

    $query = new WP_Query( [
        's'              => $keyword,
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ] );

    $results = [];
    foreach ( $query->posts as $id ) {
        $results[] = [
            'title' => get_the_title( $id ),
            'url'   => get_permalink( $id ),
        ];
    }

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_cdl_search_suggest',        'cdl_ajax_search_suggest' );
add_action( 'wp_ajax_nopriv_cdl_search_suggest', 'cdl_ajax_search_suggest' );

/**
 * 文章浏览量统计
 */
function cdl_track_post_view() {
    if ( ! is_singular( 'post' ) ) return;
    $post_id = get_the_ID();
    $count   = (int) get_post_meta( $post_id, 'post_views_count', true );
    update_post_meta( $post_id, 'post_views_count', $count + 1 );
}
add_action( 'wp_head', 'cdl_track_post_view' );
