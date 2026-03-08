<?php
/**
 * Plugin Name:  成都限行查询
 * Plugin URI:   https://github.com/zajnkda-sketch/cs
 * Description:  成都机动车尾号限行查询工具，支持今日限行展示、车牌尾号查询、近7天预览。
 * Version:      1.0.0
 * Author:       Manus AI
 * Text Domain:  cd-traffic-limit
 * License:      GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CDT_VERSION', '1.0.0' );
define( 'CDT_DIR',     plugin_dir_path( __FILE__ ) );
define( 'CDT_URI',     plugin_dir_url( __FILE__ ) );

require_once CDT_DIR . 'includes/class-traffic-limit.php';
require_once CDT_DIR . 'includes/class-traffic-admin.php';

/* ---- 初始化 ---- */
function cdt_init() {
    CD_Traffic_Limit::get_instance();
    if ( is_admin() ) {
        CD_Traffic_Admin::get_instance();
    }
}
add_action( 'plugins_loaded', 'cdt_init' );

/* ---- 激活钩子 ---- */
register_activation_hook( __FILE__, function () {
    // 写入默认限行规则选项
    if ( ! get_option( 'cdt_limit_rules' ) ) {
        update_option( 'cdt_limit_rules', [
            1 => [ 1, 6 ],
            2 => [ 2, 7 ],
            3 => [ 3, 8 ],
            4 => [ 4, 9 ],
            5 => [ 5, 0 ],
        ] );
    }
    if ( ! get_option( 'cdt_limit_time' ) ) {
        update_option( 'cdt_limit_time', '07:30 - 20:00' );
    }
    if ( ! get_option( 'cdt_limit_area' ) ) {
        update_option( 'cdt_limit_area', '绕城高速（G4202）以内所有道路' );
    }
} );
