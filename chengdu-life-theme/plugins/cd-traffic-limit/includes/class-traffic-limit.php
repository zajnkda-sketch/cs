<?php
/**
 * 限行查询核心类
 * @package CD_Traffic_Limit
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Traffic_Limit {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_assets' ] );
        add_shortcode( 'cd_traffic_limit',   [ $this, 'render_shortcode' ] );
        add_action( 'wp_ajax_cdt_query',        [ $this, 'ajax_query' ] );
        add_action( 'wp_ajax_nopriv_cdt_query', [ $this, 'ajax_query' ] );
    }

    /* ---- 注册资源 ---- */
    public function enqueue_assets() {
        if ( ! $this->is_needed() ) return;

        wp_enqueue_style(
            'cd-traffic-limit',
            CDT_URI . 'assets/css/traffic-limit.css',
            [],
            CDT_VERSION
        );
        wp_enqueue_script(
            'cd-traffic-limit',
            CDT_URI . 'assets/js/traffic-limit.js',
            [ 'jquery' ],
            CDT_VERSION,
            true
        );
        wp_localize_script( 'cd-traffic-limit', 'cdtData', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cdt_nonce' ),
            'rules'   => $this->get_rules(),
            'time'    => get_option( 'cdt_limit_time', '07:30 - 20:00' ),
            'area'    => get_option( 'cdt_limit_area', '绕城高速（G4202）以内' ),
        ] );
    }

    private function is_needed() {
        global $post;
        return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'cd_traffic_limit' );
    }

    /* ---- 获取限行规则 ---- */
    public function get_rules() {
        return get_option( 'cdt_limit_rules', [
            1 => [ 1, 6 ],
            2 => [ 2, 7 ],
            3 => [ 3, 8 ],
            4 => [ 4, 9 ],
            5 => [ 5, 0 ],
        ] );
    }

    /* ---- 计算指定日期的限行尾号 ---- */
    public function get_limit_for_date( $date_str = null ) {
        $tz    = new DateTimeZone( 'Asia/Shanghai' );
        $date  = $date_str ? new DateTime( $date_str, $tz ) : new DateTime( 'now', $tz );
        $wday  = (int) $date->format( 'N' ); // 1=Mon … 7=Sun
        $rules = $this->get_rules();

        // 节假日检查（可扩展）
        $holidays = get_option( 'cdt_holidays', [] );
        $date_key = $date->format( 'Y-m-d' );
        if ( in_array( $date_key, $holidays, true ) ) {
            return [ 'is_workday' => false, 'nums' => [], 'date' => $date_key, 'weekday' => $wday ];
        }

        $is_workday = $wday <= 5;
        $nums       = $is_workday ? ( $rules[ $wday ] ?? [] ) : [];

        return [
            'is_workday' => $is_workday,
            'nums'       => $nums,
            'date'       => $date_key,
            'weekday'    => $wday,
        ];
    }

    /* ---- 查询车牌是否限行 ---- */
    public function check_plate( $plate, $date_str = null ) {
        $plate = strtoupper( trim( $plate ) );
        if ( empty( $plate ) ) return null;

        // 提取尾号（最后一位数字）
        preg_match( '/(\d)[A-Z0-9]*$/', $plate, $m );
        if ( empty( $m[1] ) ) return null;
        $tail = (int) $m[1];

        $info = $this->get_limit_for_date( $date_str );
        $info['tail']       = $tail;
        $info['plate']      = $plate;
        $info['is_limited'] = $info['is_workday'] && in_array( $tail, $info['nums'], true );

        return $info;
    }

    /* ---- 获取近 N 天限行预览 ---- */
    public function get_week_preview( $days = 7 ) {
        $preview = [];
        $tz      = new DateTimeZone( 'Asia/Shanghai' );
        $today   = new DateTime( 'now', $tz );
        $day_names = [ 1 => '周一', 2 => '周二', 3 => '周三', 4 => '周四', 5 => '周五', 6 => '周六', 7 => '周日' ];

        for ( $i = 0; $i < $days; $i++ ) {
            $d    = clone $today;
            $d->modify( "+{$i} day" );
            $info = $this->get_limit_for_date( $d->format( 'Y-m-d' ) );
            $preview[] = array_merge( $info, [
                'label'    => $i === 0 ? '今天' : ( $i === 1 ? '明天' : $day_names[ $info['weekday'] ] ),
                'date_fmt' => $d->format( 'm/d' ),
            ] );
        }
        return $preview;
    }

    /* ---- AJAX 处理 ---- */
    public function ajax_query() {
        check_ajax_referer( 'cdt_nonce', 'nonce' );

        $action = sanitize_text_field( $_POST['sub_action'] ?? 'today' );

        switch ( $action ) {
            case 'check_plate':
                $plate = sanitize_text_field( $_POST['plate'] ?? '' );
                $date  = sanitize_text_field( $_POST['date']  ?? '' );
                $result = $this->check_plate( $plate, $date ?: null );
                if ( $result === null ) {
                    wp_send_json_error( '请输入有效的车牌号码' );
                }
                wp_send_json_success( $result );
                break;

            case 'week_preview':
                wp_send_json_success( $this->get_week_preview( 7 ) );
                break;

            default:
                wp_send_json_success( $this->get_limit_for_date() );
        }
    }

    /* ---- 短代码渲染 ---- */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'show_preview' => 'yes',
            'show_checker' => 'yes',
        ], $atts );

        $today   = $this->get_limit_for_date();
        $preview = $this->get_week_preview( 7 );
        $time    = get_option( 'cdt_limit_time', '07:30 - 20:00' );
        $area    = get_option( 'cdt_limit_area', '绕城高速（G4202）以内' );

        ob_start();
        include CDT_DIR . 'includes/template-traffic.php';
        return ob_get_clean();
    }
}
