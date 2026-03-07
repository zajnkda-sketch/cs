<?php
/**
 * Plugin Name: Fertilizer Platform
 * Plugin URI:  https://example.com
 * Description: 化肥价格实时数据平台插件 — 提供价格数据管理、REST API 接口和后台历史数据展示。
 * Version:     2.1.0
 * Author:      Manus
 * License:     GPL v2 or later
 * Text Domain: fertp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FERTP_VERSION', '2.1.0' );
define( 'FERTP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FERTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ============================================================
// 1. 激活 / 建表
// ============================================================

register_activation_hook( __FILE__, 'fertp_activate' );

function fertp_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    // 价格品种表
    $items_table = $wpdb->prefix . 'fertp_price_items';
    $sql_items = "CREATE TABLE IF NOT EXISTS `{$items_table}` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `slug`       VARCHAR(64)  NOT NULL,
        `name`       VARCHAR(128) NOT NULL,
        `unit`       VARCHAR(32)  NOT NULL DEFAULT '元/吨',
        `category`   VARCHAR(64)  NOT NULL DEFAULT '',
        `region`     VARCHAR(64)  NOT NULL DEFAULT '',
        `spec`       VARCHAR(64)  NOT NULL DEFAULT '',
        `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) {$charset};";

    // 价格历史表
    $points_table = $wpdb->prefix . 'fertp_price_points';
    $sql_points = "CREATE TABLE IF NOT EXISTS `{$points_table}` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `item_slug`  VARCHAR(64)  NOT NULL,
        `pdate`      DATE         NOT NULL,
        `price`      DECIMAL(10,2) NOT NULL,
        `source`     VARCHAR(64)  NOT NULL DEFAULT '',
        `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug_date` (`item_slug`, `pdate`),
        KEY `pdate` (`pdate`)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_items );
    dbDelta( $sql_points );

    // 预置品种数据
    $defaults = [
        [ 'urea',     '尿素',     '元/吨', 'nitrogen',   '', '' ],
        [ 'dap',      '磷酸二铵', '元/吨', 'phosphate',  '', '' ],
        [ 'mop',      '氯化钾',   '元/吨', 'potassium',  '', '' ],
        [ 'compound', '复合肥',   '元/吨', 'compound',   '', '' ],
    ];
    foreach ( $defaults as $row ) {
        $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO `{$items_table}` (slug, name, unit, category, region, spec) VALUES (%s, %s, %s, %s, %s, %s)",
            $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]
        ) );
    }
}

// ============================================================
// 2. 注册脚本 / 样式
// ============================================================

add_action( 'wp_enqueue_scripts', 'fertp_enqueue_scripts' );

function fertp_enqueue_scripts() {
    // Chart.js（如主题未加载则由插件补充）
    if ( ! wp_script_is( 'chartjs', 'registered' ) ) {
        wp_register_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
    }

    wp_enqueue_script(
        'fertp-js',
        FERTP_PLUGIN_URL . 'assets/fertp.js',
        [ 'chartjs' ],
        FERTP_VERSION,
        true
    );

    // 将 REST API base 和 nonce 注入前端
    wp_localize_script( 'fertp-js', 'FERTP', [
        'rest'  => esc_url_raw( rest_url( 'fert/v1/' ) ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
    ] );
}

// ============================================================
// 3. REST API 端点
// ============================================================

add_action( 'rest_api_init', 'fertp_register_rest_routes' );

function fertp_register_rest_routes() {
    // GET /wp-json/fert/v1/prices?slug=urea&days=90
    register_rest_route( 'fert/v1', '/prices', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'fertp_rest_get_prices',
        'permission_callback' => '__return_true',
        'args'                => [
            'slug' => [
                'required'          => true,
                'sanitize_callback' => 'sanitize_key',
            ],
            'days' => [
                'default'           => 90,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );

    // GET /wp-json/fert/v1/items — 获取所有品种及最新价格
    register_rest_route( 'fert/v1', '/items', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'fertp_rest_get_items',
        'permission_callback' => '__return_true',
    ] );
}

/**
 * 返回指定品种近 N 天的价格序列
 * 响应格式：{ "series": [ { "date": "2026-03-01", "price": 1980.00 }, ... ] }
 */
function fertp_rest_get_prices( WP_REST_Request $request ) {
    global $wpdb;
    $slug  = $request->get_param( 'slug' );
    $days  = max( 7, min( 365, (int) $request->get_param( 'days' ) ) );
    $table = $wpdb->prefix . 'fertp_price_points';

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pdate AS `date`, price
           FROM `{$table}`
          WHERE item_slug = %s
            AND pdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
          ORDER BY pdate ASC",
        $slug,
        $days
    ) );

    if ( $wpdb->last_error ) {
        return new WP_Error( 'db_error', $wpdb->last_error, [ 'status' => 500 ] );
    }

    // 格式化数字
    $series = array_map( function( $row ) {
        return [
            'date'  => $row->date,
            'price' => (float) $row->price,
        ];
    }, $rows );

    return rest_ensure_response( [ 'series' => $series ] );
}

/**
 * 返回所有品种及其最新价格
 */
function fertp_rest_get_items( WP_REST_Request $request ) {
    global $wpdb;
    $items_table  = $wpdb->prefix . 'fertp_price_items';
    $points_table = $wpdb->prefix . 'fertp_price_points';

    $rows = $wpdb->get_results(
        "SELECT i.slug, i.name, i.unit, i.category, i.region, i.spec,
                p.price AS latest_price, p.pdate AS latest_date
           FROM `{$items_table}` i
      LEFT JOIN `{$points_table}` p
             ON p.item_slug = i.slug
            AND p.pdate = (
                    SELECT MAX(pdate) FROM `{$points_table}` WHERE item_slug = i.slug
                )
          ORDER BY i.id ASC"
    );

    return rest_ensure_response( $rows );
}

// ============================================================
// 4. AJAX 处理（供主题 main.js 使用）
// ============================================================

add_action( 'wp_ajax_get_price_chart_data',        'fertp_ajax_get_price_chart_data' );
add_action( 'wp_ajax_nopriv_get_price_chart_data', 'fertp_ajax_get_price_chart_data' );

function fertp_ajax_get_price_chart_data() {
    check_ajax_referer( 'fertilizer_nonce', 'nonce' );

    global $wpdb;
    $slug  = sanitize_key( $_POST['product'] ?? 'urea' );
    $days  = max( 7, min( 365, (int) ( $_POST['days'] ?? 30 ) ) );
    $table = $wpdb->prefix . 'fertp_price_points';

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pdate AS `date`, price
           FROM `{$table}`
          WHERE item_slug = %s
            AND pdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
          ORDER BY pdate ASC",
        $slug,
        $days
    ) );

    if ( $wpdb->last_error ) {
        wp_send_json_error( [ 'message' => $wpdb->last_error ] );
    }

    $data = array_map( function( $row ) {
        return [
            'date'  => $row->date,
            'price' => (float) $row->price,
        ];
    }, $rows );

    wp_send_json_success( $data );
}

// ============================================================
// 5. 后台管理菜单
// ============================================================

add_action( 'admin_menu', 'fertp_admin_menu' );

function fertp_admin_menu() {
    add_menu_page(
        '化肥价格管理',
        '化肥价格',
        'manage_options',
        'fertp-dashboard',
        'fertp_admin_dashboard',
        'dashicons-chart-line',
        30
    );

    add_submenu_page(
        'fertp-dashboard',
        '价格历史数据',
        '历史数据',
        'manage_options',
        'fertp-history',
        'fertp_admin_history'
    );

    add_submenu_page(
        'fertp-dashboard',
        '手动录入价格',
        '录入价格',
        'manage_options',
        'fertp-add-price',
        'fertp_admin_add_price'
    );
}

// ============================================================
// 6. 后台页面：概览仪表盘
// ============================================================

function fertp_admin_dashboard() {
    global $wpdb;
    $points_table = $wpdb->prefix . 'fertp_price_points';
    $items_table  = $wpdb->prefix . 'fertp_price_items';

    $total_records = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$points_table}`" );
    $latest_date   = $wpdb->get_var( "SELECT MAX(pdate) FROM `{$points_table}`" );
    $items_count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$items_table}`" );

    // 各品种最新价格
    $latest_prices = $wpdb->get_results(
        "SELECT i.name, i.slug, p.price, p.pdate, p.source
           FROM `{$items_table}` i
      LEFT JOIN `{$points_table}` p
             ON p.item_slug = i.slug
            AND p.pdate = (SELECT MAX(pdate) FROM `{$points_table}` WHERE item_slug = i.slug)
          ORDER BY i.id ASC"
    );
    ?>
    <div class="wrap">
        <h1>化肥价格平台 — 概览</h1>

        <div style="display:flex;gap:20px;margin:20px 0;flex-wrap:wrap;">
            <div class="postbox" style="min-width:160px;padding:16px 24px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#28a745;"><?php echo esc_html( $total_records ); ?></div>
                <div>历史价格记录总数</div>
            </div>
            <div class="postbox" style="min-width:160px;padding:16px 24px;text-align:center;">
                <div style="font-size:2rem;font-weight:700;color:#17a2b8;"><?php echo esc_html( $items_count ); ?></div>
                <div>品种数量</div>
            </div>
            <div class="postbox" style="min-width:160px;padding:16px 24px;text-align:center;">
                <div style="font-size:1.4rem;font-weight:700;color:#6c757d;"><?php echo esc_html( $latest_date ?: '暂无数据' ); ?></div>
                <div>最新数据日期</div>
            </div>
        </div>

        <h2>各品种最新价格</h2>
        <table class="wp-list-table widefat fixed striped" style="max-width:700px;">
            <thead>
                <tr>
                    <th>品种</th>
                    <th>最新价格（元/吨）</th>
                    <th>数据日期</th>
                    <th>来源</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $latest_prices ) ) : ?>
                    <tr><td colspan="4" style="color:#999;">暂无价格数据，请运行 auto_price_update.php 或手动录入。</td></tr>
                <?php else : ?>
                    <?php foreach ( $latest_prices as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row->name ); ?></td>
                            <td><?php echo $row->price ? number_format( (float) $row->price, 2 ) : '<span style="color:#999;">—</span>'; ?></td>
                            <td><?php echo esc_html( $row->pdate ?: '—' ); ?></td>
                            <td><?php echo esc_html( $row->source ?: '—' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p style="margin-top:20px;">
            <a href="<?php echo admin_url( 'admin.php?page=fertp-history' ); ?>" class="button button-primary">查看完整历史数据</a>
            &nbsp;
            <a href="<?php echo admin_url( 'admin.php?page=fertp-add-price' ); ?>" class="button">手动录入价格</a>
        </p>
    </div>
    <?php
}

// ============================================================
// 7. 后台页面：历史数据列表
// ============================================================

function fertp_admin_history() {
    global $wpdb;
    $points_table = $wpdb->prefix . 'fertp_price_points';
    $items_table  = $wpdb->prefix . 'fertp_price_items';

    // 筛选参数
    $filter_slug = sanitize_key( $_GET['slug'] ?? '' );
    $filter_from = sanitize_text_field( $_GET['from'] ?? '' );
    $filter_to   = sanitize_text_field( $_GET['to']   ?? '' );
    $paged       = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
    $per_page    = 50;
    $offset      = ( $paged - 1 ) * $per_page;

    // 构建 WHERE 子句
    $where  = '1=1';
    $params = [];
    if ( $filter_slug ) {
        $where   .= ' AND p.item_slug = %s';
        $params[] = $filter_slug;
    }
    if ( $filter_from ) {
        $where   .= ' AND p.pdate >= %s';
        $params[] = $filter_from;
    }
    if ( $filter_to ) {
        $where   .= ' AND p.pdate <= %s';
        $params[] = $filter_to;
    }

    $base_sql = "FROM `{$points_table}` p
                 LEFT JOIN `{$items_table}` i ON i.slug = p.item_slug
                 WHERE {$where}";

    $count_sql = "SELECT COUNT(*) {$base_sql}";
    $data_sql  = "SELECT p.id, p.item_slug, i.name AS item_name, p.pdate, p.price, p.source, p.created_at
                  {$base_sql}
                  ORDER BY p.pdate DESC, p.item_slug ASC
                  LIMIT %d OFFSET %d";

    if ( ! empty( $params ) ) {
        $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
        $rows  = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ) );
    } else {
        $total = (int) $wpdb->get_var( $count_sql );
        $rows  = $wpdb->get_results( $wpdb->prepare( $data_sql, $per_page, $offset ) );
    }

    $total_pages = max( 1, (int) ceil( $total / $per_page ) );

    // 品种下拉选项
    $items = $wpdb->get_results( "SELECT slug, name FROM `{$items_table}` ORDER BY id ASC" );

    // 处理删除操作
    if ( isset( $_POST['fertp_delete_id'] ) && check_admin_referer( 'fertp_delete_price' ) ) {
        $del_id = (int) $_POST['fertp_delete_id'];
        $wpdb->delete( $points_table, [ 'id' => $del_id ], [ '%d' ] );
        echo '<div class="notice notice-success"><p>记录已删除。</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>化肥价格历史数据</h1>
        <p>共 <strong><?php echo esc_html( $total ); ?></strong> 条记录，当前第 <?php echo esc_html( $paged ); ?> / <?php echo esc_html( $total_pages ); ?> 页</p>

        <!-- 筛选表单 -->
        <form method="get" action="" style="margin-bottom:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <input type="hidden" name="page" value="fertp-history">
            <div>
                <label style="display:block;font-weight:600;">品种</label>
                <select name="slug">
                    <option value="">全部</option>
                    <?php foreach ( $items as $item ) : ?>
                        <option value="<?php echo esc_attr( $item->slug ); ?>" <?php selected( $filter_slug, $item->slug ); ?>>
                            <?php echo esc_html( $item->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;">开始日期</label>
                <input type="date" name="from" value="<?php echo esc_attr( $filter_from ); ?>">
            </div>
            <div>
                <label style="display:block;font-weight:600;">结束日期</label>
                <input type="date" name="to" value="<?php echo esc_attr( $filter_to ); ?>">
            </div>
            <div>
                <button type="submit" class="button button-primary">筛选</button>
                <a href="<?php echo admin_url( 'admin.php?page=fertp-history' ); ?>" class="button">重置</a>
            </div>
        </form>

        <!-- 数据表格 -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>品种</th>
                    <th>日期</th>
                    <th>价格（元/吨）</th>
                    <th>来源</th>
                    <th>录入时间</th>
                    <th style="width:80px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $rows ) ) : ?>
                    <tr>
                        <td colspan="7" style="color:#999;padding:20px;text-align:center;">
                            暂无数据。请确认 auto_price_update.php 已成功运行，或通过「录入价格」手动添加数据。
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $rows as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row->id ); ?></td>
                            <td><?php echo esc_html( $row->item_name ?: $row->item_slug ); ?></td>
                            <td><?php echo esc_html( $row->pdate ); ?></td>
                            <td><strong><?php echo number_format( (float) $row->price, 2 ); ?></strong></td>
                            <td><?php echo esc_html( $row->source ?: '—' ); ?></td>
                            <td style="font-size:12px;color:#999;"><?php echo esc_html( $row->created_at ); ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('确认删除此记录？');">
                                    <?php wp_nonce_field( 'fertp_delete_price' ); ?>
                                    <input type="hidden" name="fertp_delete_id" value="<?php echo esc_attr( $row->id ); ?>">
                                    <button type="submit" class="button button-small" style="color:#dc3545;border-color:#dc3545;">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 分页 -->
        <?php if ( $total_pages > 1 ) : ?>
            <div style="margin-top:16px;">
                <?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
                    <?php
                    $url = add_query_arg( [
                        'page'  => 'fertp-history',
                        'paged' => $p,
                        'slug'  => $filter_slug,
                        'from'  => $filter_from,
                        'to'    => $filter_to,
                    ], admin_url( 'admin.php' ) );
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>"
                       class="button <?php echo ( $p === $paged ) ? 'button-primary' : ''; ?>"
                       style="margin-right:4px;"><?php echo esc_html( $p ); ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// ============================================================
// 8. 后台页面：手动录入价格
// ============================================================

function fertp_admin_add_price() {
    global $wpdb;
    $points_table = $wpdb->prefix . 'fertp_price_points';
    $items_table  = $wpdb->prefix . 'fertp_price_items';

    $message = '';
    $error   = '';

    if ( isset( $_POST['fertp_add_price'] ) && check_admin_referer( 'fertp_add_price_action' ) ) {
        $slug  = sanitize_key( $_POST['item_slug'] ?? '' );
        $pdate = sanitize_text_field( $_POST['pdate'] ?? '' );
        $price = (float) ( $_POST['price'] ?? 0 );

        if ( ! $slug || ! $pdate || $price <= 0 ) {
            $error = '请填写完整的品种、日期和价格（价格须大于 0）。';
        } else {
            // UPSERT：当日已有记录则更新
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM `{$points_table}` WHERE item_slug = %s AND pdate = %s",
                $slug, $pdate
            ) );

            if ( $existing ) {
                $result = $wpdb->update(
                    $points_table,
                    [ 'price' => $price, 'source' => 'manual' ],
                    [ 'id' => $existing ],
                    [ '%f', '%s' ],
                    [ '%d' ]
                );
                $message = $result !== false ? '价格已更新。' : '更新失败：' . $wpdb->last_error;
            } else {
                $result = $wpdb->insert(
                    $points_table,
                    [
                        'item_slug'  => $slug,
                        'pdate'      => $pdate,
                        'price'      => $price,
                        'source'     => 'manual',
                        'created_at' => current_time( 'mysql' ),
                    ],
                    [ '%s', '%s', '%f', '%s', '%s' ]
                );
                $message = $result ? '价格录入成功。' : '录入失败：' . $wpdb->last_error;
            }

            // 同步更新 items 表时间戳
            if ( $result !== false ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE `{$items_table}` SET updated_at = NOW() WHERE slug = %s",
                    $slug
                ) );
            }
        }
    }

    $items = $wpdb->get_results( "SELECT slug, name FROM `{$items_table}` ORDER BY id ASC" );
    ?>
    <div class="wrap">
        <h1>手动录入价格</h1>

        <?php if ( $message ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
        <?php endif; ?>
        <?php if ( $error ) : ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
        <?php endif; ?>

        <form method="post" style="max-width:480px;margin-top:20px;">
            <?php wp_nonce_field( 'fertp_add_price_action' ); ?>
            <input type="hidden" name="fertp_add_price" value="1">

            <table class="form-table">
                <tr>
                    <th><label for="item_slug">品种</label></th>
                    <td>
                        <select name="item_slug" id="item_slug" required>
                            <option value="">— 请选择 —</option>
                            <?php foreach ( $items as $item ) : ?>
                                <option value="<?php echo esc_attr( $item->slug ); ?>">
                                    <?php echo esc_html( $item->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="pdate">日期</label></th>
                    <td>
                        <input type="date" name="pdate" id="pdate"
                               value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="price">价格（元/吨）</label></th>
                    <td>
                        <input type="number" name="price" id="price"
                               min="1" max="99999" step="0.01"
                               placeholder="例如：1980.00" required style="width:200px;">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">录入价格</button>
                <a href="<?php echo admin_url( 'admin.php?page=fertp-history' ); ?>" class="button">查看历史数据</a>
            </p>
        </form>

        <hr style="margin:30px 0;">
        <h3>说明</h3>
        <ul style="list-style:disc;padding-left:20px;line-height:2;">
            <li>若该品种当日已有记录，提交后将<strong>覆盖</strong>原价格。</li>
            <li>自动更新脚本（<code>auto_price_update.php</code>）每日 09:00 自动写入，无需手动操作。</li>
            <li>录入后前端折线图将在下次刷新时自动更新。</li>
        </ul>
    </div>
    <?php
}

// ============================================================
// 9. 主题 functions.php 辅助：注入 AJAX nonce（供 main.js 使用）
// ============================================================

add_action( 'wp_enqueue_scripts', 'fertp_localize_theme_ajax' );

function fertp_localize_theme_ajax() {
    // 如果主题已加载 main.js，则为其注入 nonce；否则由插件自行注册
    if ( wp_script_is( 'fertilizer-main', 'registered' ) || wp_script_is( 'fertilizer-main', 'enqueued' ) ) {
        wp_localize_script( 'fertilizer-main', 'fertilizer_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fertilizer_nonce' ),
        ] );
    } else {
        // 直接输出内联脚本，确保 main.js 能获取到 fertilizer_ajax 对象
        add_action( 'wp_footer', function() {
            echo '<script>window.fertilizer_ajax = ' . wp_json_encode( [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'fertilizer_nonce' ),
            ] ) . ';</script>' . "\n";
        }, 1 );
    }
}
