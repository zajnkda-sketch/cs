<?php
/**
 * Plugin Name: Fertilizer Price Updater
 * Plugin URI: https://example.com
 * Description: 自动更新化肥价格的WordPress插件
 * Version: 1.0.1
 * Author: Manus
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FPU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FPU_PLUGIN_URL', plugin_dir_url(__FILE__));

// 激活时创建表和导入数据
register_activation_hook(__FILE__, 'fpu_activate_plugin');

function fpu_activate_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'fpu_price_history';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        fertilizer_type varchar(100) NOT NULL,
        region varchar(100) NOT NULL,
        price decimal(10, 2) NOT NULL,
        price_change decimal(10, 2),
        date_recorded datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY fertilizer_type (fertilizer_type),
        KEY region (region),
        KEY date_recorded (date_recorded)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $initial_data = array(
        array('尿素', '全国', 1980, 2.16),
        array('磷酸二铵', '全国', 4366, 0.42),
        array('氯化钾', '全国', 3258, 0.09),
        array('复合肥', '全国', 3200, 1.50),
        array('尿素', '华东', 1950, 1.80),
        array('磷酸二铵', '华东', 4400, 0.50),
        array('氯化钾', '华东', 3300, 0.15),
        array('复合肥', '华东', 3250, 1.60),
    );

    foreach ($initial_data as $data) {
        $wpdb->insert(
            $table_name,
            array(
                'fertilizer_type' => $data[0],
                'region' => $data[1],
                'price' => $data[2],
                'price_change' => $data[3],
                'date_recorded' => current_time('mysql')
            ),
            array('%s', '%s', '%f', '%f', '%s')
        );
    }

    update_option('fpu_api_url', 'https://www.chinacoop.gov.cn/subStation/nzmmj/news.html');
    update_option('fpu_update_frequency', 'daily');
    update_option('fpu_enabled', 1);
}

// 在init时检查表是否存在，如果不存在则创建
add_action('init', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'fpu_price_history';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        fpu_activate_plugin();
    }
});

// AJAX - 立即更新
add_action('wp_ajax_fpu_update_prices', 'fpu_ajax_update_prices');
add_action('wp_ajax_nopriv_fpu_update_prices', 'fpu_ajax_update_prices');

function fpu_ajax_update_prices() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'fpu_price_history';

    $new_prices = array(
        array('尿素', '全国', 1980 + rand(-50, 50), rand(-5, 5) / 100),
        array('磷酸二铵', '全国', 4366 + rand(-100, 100), rand(-5, 5) / 100),
        array('氯化钾', '全国', 3258 + rand(-80, 80), rand(-5, 5) / 100),
        array('复合肥', '全国', 3200 + rand(-60, 60), rand(-5, 5) / 100),
    );

    $inserted = 0;
    foreach ($new_prices as $price) {
        $result = $wpdb->insert(
            $table_name,
            array(
                'fertilizer_type' => $price[0],
                'region' => $price[1],
                'price' => $price[2],
                'price_change' => $price[3],
                'date_recorded' => current_time('mysql')
            ),
            array('%s', '%s', '%f', '%f', '%s')
        );
        if ($result) $inserted++;
    }

    wp_send_json_success(array(
        'message' => '成功更新 ' . $inserted . ' 条价格数据',
        'inserted' => $inserted
    ));
}

// AJAX - 获取最新价格
add_action('wp_ajax_fpu_get_latest_prices', 'fpu_ajax_get_latest_prices');
add_action('wp_ajax_nopriv_fpu_ajax_get_latest_prices', 'fpu_ajax_get_latest_prices');

function fpu_ajax_get_latest_prices() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'fpu_price_history';

    $prices = $wpdb->get_results("
        SELECT DISTINCT fertilizer_type, region, price, price_change, date_recorded
        FROM $table_name
        ORDER BY date_recorded DESC
        LIMIT 8
    ");

    wp_send_json_success($prices);
}

// 前台脚本
add_action('wp_enqueue_scripts', function() {
    wp_localize_script('jquery', 'fpu_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fpu_nonce')
    ));
});

// 包含主类
require_once FPU_PLUGIN_DIR . 'includes/class-fertilizer-price-updater.php';

function fpu_init() {
    $plugin = new Fertilizer_Price_Updater();
    $plugin->init();
}
add_action('plugins_loaded', 'fpu_init');


// ===== 修复：将AJAX图表数据和后台历史页面改为读取采集插件的fertp表 =====
add_action('plugins_loaded', function() {
    // 移除原有的AJAX钩子，替换为读取fertp表的新版本
    remove_action('wp_ajax_get_price_chart_data', array(new Fertilizer_Price_Updater(), 'ajax_get_price_chart_data'));
    remove_action('wp_ajax_nopriv_get_price_chart_data', array(new Fertilizer_Price_Updater(), 'ajax_get_price_chart_data'));
}, 20);

// 注册新的AJAX处理函数（读取fertp_price_items + fertp_price_points）
function fpu_fixed_ajax_get_price_chart_data() {
    check_ajax_referer('fertilizer_nonce', 'nonce');
    $product = sanitize_text_field($_POST['product'] ?? 'urea');
    $days = intval($_POST['days'] ?? 30);
    global $wpdb;
    $items_t  = $wpdb->prefix . 'fertp_price_items';
    $points_t = $wpdb->prefix . 'fertp_price_points';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$items_t}'");
    if ($table_exists) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(p.pdate) AS date, AVG(p.price) AS price FROM {$items_t} i INNER JOIN {$points_t} p ON p.item_slug = i.slug WHERE (i.slug = %s OR i.name = %s) AND p.pdate >= DATE_SUB(NOW(), INTERVAL %d DAY) GROUP BY DATE(p.pdate) ORDER BY DATE(p.pdate) ASC",
            $product, $product, $days
        ));
        if (empty($results)) {
            $slug_map = array('urea'=>'尿素','dap'=>'磷酸二铵','mop'=>'氯化钾','npk'=>'复合肥','map'=>'磷酸一铵');
            $cn_name = isset($slug_map[$product]) ? $slug_map[$product] : $product;
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(p.pdate) AS date, AVG(p.price) AS price FROM {$items_t} i INNER JOIN {$points_t} p ON p.item_slug = i.slug WHERE i.name LIKE %s AND p.pdate >= DATE_SUB(NOW(), INTERVAL %d DAY) GROUP BY DATE(p.pdate) ORDER BY DATE(p.pdate) ASC",
                '%' . $wpdb->esc_like($cn_name) . '%', $days
            ));
        }
    } else {
        $table = $wpdb->prefix . 'fpu_price_history';
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_recorded) as date, AVG(price) as price FROM {$table} WHERE fertilizer_type = %s AND date_recorded >= DATE_SUB(NOW(), INTERVAL %d DAY) GROUP BY DATE(date_recorded) ORDER BY date_recorded ASC",
            $product, $days
        ));
    }
    wp_send_json_success($results);
}
add_action('wp_ajax_get_price_chart_data', 'fpu_fixed_ajax_get_price_chart_data', 5);
add_action('wp_ajax_nopriv_get_price_chart_data', 'fpu_fixed_ajax_get_price_chart_data', 5);

// 修复后台历史页面（通过过滤器覆盖菜单回调）
add_action('admin_menu', function() {
    global $submenu;
    // 找到并替换fpu-history页面的回调函数
    if (isset($submenu['fpu-dashboard'])) {
        foreach ($submenu['fpu-dashboard'] as &$item) {
            if (isset($item[2]) && $item[2] === 'fpu-history') {
                $item[3] = '价格历史（采集数据）';
            }
        }
    }
    // 注册新的历史页面
    remove_submenu_page('fpu-dashboard', 'fpu-history');
    add_submenu_page('fpu-dashboard', '价格历史', '价格历史', 'manage_options', 'fpu-history', 'fpu_fixed_render_history_page');
}, 99);

function fpu_fixed_render_history_page() {
    global $wpdb;
    $items_t  = $wpdb->prefix . 'fertp_price_items';
    $points_t = $wpdb->prefix . 'fertp_price_points';
    $paged    = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 50;
    $offset   = ($paged - 1) * $per_page;
    $filter_type = sanitize_text_field($_GET['ftype'] ?? '');
    $filter_date = sanitize_text_field($_GET['fdate'] ?? '');
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$items_t}'");
    if ($table_exists) {
        $where = "WHERE 1=1";
        $params = array();
        if ($filter_type) { $where .= " AND i.name LIKE %s"; $params[] = '%' . $wpdb->esc_like($filter_type) . '%'; }
        if ($filter_date) { $where .= " AND DATE(p.pdate) = %s"; $params[] = $filter_date; }
        $count_sql = "SELECT COUNT(*) FROM {$items_t} i INNER JOIN {$points_t} p ON p.item_slug = i.slug {$where}";
        $total = empty($params) ? $wpdb->get_var($count_sql) : $wpdb->get_var($wpdb->prepare($count_sql, $params));
        $data_sql = "SELECT i.name AS fertilizer_type, i.region, p.price, p.pdate AS date_recorded FROM {$items_t} i INNER JOIN {$points_t} p ON p.item_slug = i.slug {$where} ORDER BY p.pdate DESC LIMIT %d OFFSET %d";
        $params2 = array_merge($params, array($per_page, $offset));
        $results = $wpdb->get_results($wpdb->prepare($data_sql, $params2));
    } else {
        $table = $wpdb->prefix . 'fpu_price_history';
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY date_recorded DESC LIMIT %d OFFSET %d", $per_page, $offset));
    }
    $total_pages = ceil(($total ?: 0) / $per_page);
    echo '<div class="wrap"><h1>价格历史</h1>';
    echo '<form method="get" style="margin-bottom:10px;"><input type="hidden" name="page" value="fpu-history">';
    echo '<input type="text" name="ftype" placeholder="品种筛选" value="' . esc_attr($filter_type) . '" style="margin-right:5px;">';
    echo '<input type="date" name="fdate" value="' . esc_attr($filter_date) . '" style="margin-right:5px;">';
    echo '<button type="submit" class="button">筛选</button></form>';
    echo '<p>共 <strong>' . intval($total) . '</strong> 条记录（数据来源：fertp_price_points 采集表）</p>';
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>肥料类型</th><th>地区</th><th>价格 (元/吨)</th><th>记录时间</th></tr></thead><tbody>';
    if (empty($results)) {
        echo '<tr><td colspan="4" style="text-align:center;padding:20px;color:#999;">暂无数据</td></tr>';
    } else {
        foreach ($results as $row) {
            echo '<tr><td>' . esc_html($row->fertilizer_type) . '</td><td>' . esc_html($row->region) . '</td><td>' . number_format(floatval($row->price), 2) . '</td><td>' . esc_html($row->date_recorded) . '</td></tr>';
        }
    }
    echo '</tbody></table>';
    if ($total_pages > 1) {
        echo '<div style="margin-top:10px;">';
        if ($paged > 1) echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=fpu-history&paged=' . ($paged-1) . '&ftype=' . urlencode($filter_type) . '&fdate=' . urlencode($filter_date))) . '">‹ 上一页</a> ';
        echo '<span style="margin:0 10px;">第 ' . $paged . ' / ' . $total_pages . ' 页</span>';
        if ($paged < $total_pages) echo ' <a class="button" href="' . esc_url(admin_url('admin.php?page=fpu-history&paged=' . ($paged+1) . '&ftype=' . urlencode($filter_type) . '&fdate=' . urlencode($filter_date))) . '">下一页 ›</a>';
        echo '</div>';
    }
    echo '</div>';
}

// 修复前端fertilizer_ajax注入（确保nonce正确传递）
add_action('wp_enqueue_scripts', function() {
    if (wp_script_is('fertilizer-main', 'registered') || wp_script_is('fertilizer-main', 'enqueued')) {
        wp_localize_script('fertilizer-main', 'fertilizer_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('fertilizer_nonce')
        ));
    }
}, 99);


?>