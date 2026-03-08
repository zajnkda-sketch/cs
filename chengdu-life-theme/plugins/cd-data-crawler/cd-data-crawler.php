<?php
/**
 * Plugin Name:  成都数据采集器
 * Plugin URI:   https://github.com/zajnkda-sketch/cs
 * Description:  可视化配置的网页数据采集插件，支持多任务管理、CSS选择器解析、去重机制、定时调度，用于采集官方网站公开信息。
 * Version:      1.0.0
 * Author:       Manus AI
 * Text Domain:  cd-data-crawler
 * License:      GPL v2 or later
 *
 * 重要声明：本插件仅用于采集官方网站公开发布的信息，使用者须遵守目标网站的 robots.txt 规则
 * 及相关法律法规，不得用于任何违法用途。
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CDCR_VERSION', '1.0.0' );
define( 'CDCR_DIR',     plugin_dir_path( __FILE__ ) );
define( 'CDCR_URI',     plugin_dir_url( __FILE__ ) );

require_once CDCR_DIR . 'includes/class-crawler-db.php';
require_once CDCR_DIR . 'includes/class-crawler-engine.php';
require_once CDCR_DIR . 'includes/class-crawler-admin.php';
require_once CDCR_DIR . 'includes/class-crawler-scheduler.php';

function cdcr_init() {
    CD_Crawler_Scheduler::get_instance();
    if ( is_admin() ) {
        CD_Crawler_Admin::get_instance();
    }
}
add_action( 'plugins_loaded', 'cdcr_init' );

/* ---- 激活：建表 ---- */
register_activation_hook( __FILE__, [ 'CD_Crawler_DB', 'create_tables' ] );

/* ---- 停用：清除定时任务 ---- */
register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'cdcr_run_tasks' );
} );
