<?php
/**
 * 化肥价格自动更新脚本
 * =====================
 * 用途：宝塔面板定时任务，每日自动写入化肥价格数据
 *
 * 数据库表：wp_fertp_price_points
 *   字段：id, item_slug, pdate, price, source, created_at
 *
 * 数据库表：wp_fertp_price_items
 *   字段：id, slug, name, unit, category, region, spec, updated_at
 *
 * 品种 slug 对照：
 *   urea      => 尿素
 *   dap       => 磷酸二铵
 *   mop       => 氯化钾
 *   compound  => 复合肥
 *
 * 使用方法：
 *   php /www/wwwroot/1112.chaxunwa.com/auto_price_update.php
 *
 * 宝塔定时任务（每天09:00执行）：
 *   php /www/wwwroot/1112.chaxunwa.com/auto_price_update.php
 *
 * 版本：2.2.0（修复今日检查逻辑：改为按品种逐一检查，避免部分品种缺数据时被跳过）
 * 更新：2026-03-07
 */

// ============================================================
// 配置区（如需修改请在此处调整）
// ============================================================

// WordPress 根目录（脚本放在根目录时保持默认即可）
define('WP_ROOT', __DIR__);

// 日志文件路径
define('LOG_FILE', __DIR__ . '/auto_price_update.log');

// 价格合理范围（用于模拟波动时的边界保护）
$PRICE_RANGES = [
    'urea'     => ['min' => 1400, 'max' => 2800, 'name' => '尿素'],
    'dap'      => ['min' => 3200, 'max' => 5500, 'name' => '磷酸二铵'],
    'mop'      => ['min' => 2400, 'max' => 4200, 'name' => '氯化钾'],
    'compound' => ['min' => 2400, 'max' => 4500, 'name' => '复合肥'],
];

// ============================================================
// 主程序
// ============================================================

$today = date('Y-m-d');
log_msg("========== 开始执行价格更新 ==========");
log_msg("执行时间: {$today} " . date('H:i:s'));

// 1. 读取 WordPress 数据库配置
$db = read_wp_config(WP_ROOT . '/wp-config.php');
if (!$db) {
    log_msg('无法读取 wp-config.php，请确认脚本放在 WordPress 根目录', 'ERROR');
    exit(1);
}

// 2. 连接数据库
$pdo = connect_db($db);
if (!$pdo) {
    exit(1);
}

$table_points = $db['prefix'] . 'fertp_price_points';
$table_items  = $db['prefix'] . 'fertp_price_items';
log_msg("数据库连接成功，价格表：{$table_points}，今日日期：{$today}");

// 3. 确保数据库表存在（兼容首次运行）
ensure_tables($pdo, $db['prefix']);

// 4. 获取价格（优先网络，失败则模拟）
log_msg("开始抓取价格数据...");
$prices = fetch_prices($pdo, $table_points, $table_items, $PRICE_RANGES);

// 5. 逐品种写入数据库（改为按品种检查，避免部分品种缺数据时被整体跳过）
$success = 0;
$skipped = 0;
$fail    = 0;

foreach ($prices as $slug => $price) {
    $name = $PRICE_RANGES[$slug]['name'] ?? $slug;

    // 检查该品种今日是否已有来源为 plugin/auto_script 的记录
    // 若已有记录则跳过（避免覆盖插件采集的真实数据）
    if (check_slug_today($pdo, $table_points, $slug, $today)) {
        log_msg("→ 跳过（今日已有数据）：{$name}（{$slug}）");
        $skipped++;
        continue;
    }

    $result = insert_price($pdo, $table_points, $slug, $today, $price);
    if ($result) {
        log_msg("✓ 写入成功：{$name}（{$slug}）= {$price} 元/吨");
        // 同步更新 items 表的 updated_at
        update_item_timestamp($pdo, $table_items, $slug);
        $success++;
    } else {
        log_msg("✗ 写入失败：{$name}（{$slug}）", 'WARN');
        $fail++;
    }
}

log_msg("执行完成：成功 {$success} 条，跳过 {$skipped} 条，失败 {$fail} 条");
log_msg("========== 执行结束 ==========");
echo str_repeat('-', 70) . "\n";
echo "★[" . date('Y-m-d H:i:s') . "] " . ($fail === 0 ? "Successful" : "Completed with {$fail} failures") . "\n";
echo str_repeat('-', 70) . "\n";

// ============================================================
// 函数定义
// ============================================================

/**
 * 写日志（同时输出到终端和日志文件）
 */
function log_msg($msg, $level = 'INFO') {
    $line = '[' . date('Y-m-d H:i:s') . "] [{$level}] {$msg}";
    echo $line . "\n";
    file_put_contents(LOG_FILE, $line . "\n", FILE_APPEND);
}

/**
 * 读取 wp-config.php 中的数据库配置
 */
function read_wp_config($config_file) {
    if (!file_exists($config_file)) {
        log_msg("wp-config.php 不存在：{$config_file}", 'ERROR');
        return false;
    }
    $content = file_get_contents($config_file);
    $cfg = [];
    foreach ([
        'DB_NAME'     => 'name',
        'DB_USER'     => 'user',
        'DB_PASSWORD' => 'pass',
        'DB_HOST'     => 'host',
    ] as $const => $key) {
        if (preg_match("/define\s*\(\s*['\"]" . $const . "['\"]\s*,\s*['\"]([^'\"]*)['\"]/",$content,$m)) {
            $cfg[$key] = $m[1];
        } else {
            log_msg("wp-config.php 中未找到 {$const}", 'ERROR');
            return false;
        }
    }
    // 读取表前缀
    if (preg_match('/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
        $cfg['prefix'] = $m[1];
    } else {
        $cfg['prefix'] = 'wp_';
    }
    return $cfg;
}

/**
 * 建立 PDO 数据库连接
 */
function connect_db($db) {
    try {
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 10,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        log_msg('数据库连接异常：' . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 确保数据库表存在（兼容插件未激活时直接运行脚本的场景）
 */
function ensure_tables($pdo, $prefix) {
    $items_table  = $prefix . 'fertp_price_items';
    $points_table = $prefix . 'fertp_price_points';

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$items_table}` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$points_table}` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `item_slug`  VARCHAR(64)  NOT NULL,
            `pdate`      DATE         NOT NULL,
            `price`      DECIMAL(10,2) NOT NULL,
            `source`     VARCHAR(64)  NOT NULL DEFAULT '',
            `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug_date` (`item_slug`, `pdate`),
            KEY `pdate` (`pdate`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 预置品种数据
        $defaults = [
            ['urea',     '尿素',     '元/吨', 'nitrogen',  '', ''],
            ['dap',      '磷酸二铵', '元/吨', 'phosphate', '', ''],
            ['mop',      '氯化钾',   '元/吨', 'potassium', '', ''],
            ['compound', '复合肥',   '元/吨', 'compound',  '', ''],
        ];
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO `{$items_table}` (slug, name, unit, category, region, spec) VALUES (?, ?, ?, ?, ?, ?)"
        );
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }

        log_msg("数据库表检查完成");
    } catch (PDOException $e) {
        log_msg('建表失败：' . $e->getMessage(), 'WARN');
    }
}

/**
 * 检查指定品种今日是否已有数据
 * （修复原版按全部品种检查的问题，改为按 slug 单独检查）
 */
function check_slug_today($pdo, $table, $slug, $today) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE item_slug = ? AND pdate = ?"
        );
        $stmt->execute([$slug, $today]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        log_msg("检查 {$slug} 今日数据失败：" . $e->getMessage(), 'WARN');
        return false;
    }
}

/**
 * 获取价格数据
 * 优先从网络抓取，失败则使用基于历史数据的模拟价格
 */
function fetch_prices($pdo, $table_points, $table_items, $ranges) {
    // 方法1：尝试从网络抓取
    $fetched = fetch_from_web(array_keys($ranges));
    if (!empty($fetched)) {
        log_msg('网络抓取成功，使用实时数据');
        return $fetched;
    }

    // 方法2：基于数据库最近一次价格，生成模拟波动价格
    log_msg('网络抓取失败，使用历史数据模拟波动', 'WARN');
    return generate_simulated_prices($pdo, $table_points, $ranges);
}

/**
 * 从网络抓取价格（预留接口，可对接真实数据源）
 * 返回格式：['urea' => 1980.00, 'dap' => 4366.00, ...]
 * 若无法获取则返回空数组
 */
function fetch_from_web($slugs) {
    // -------------------------------------------------------
    // 如需对接真实数据源，在此处实现抓取逻辑
    // 示例：对接内部 API
    // $url = 'https://your-data-api.com/prices?key=YOUR_KEY';
    // $resp = @file_get_contents($url);
    // if ($resp) {
    //     $data = json_decode($resp, true);
    //     return [
    //         'urea'     => $data['urea_price'],
    //         'dap'      => $data['dap_price'],
    //         'mop'      => $data['mop_price'],
    //         'compound' => $data['compound_price'],
    //     ];
    // }
    // -------------------------------------------------------
    return [];
}

/**
 * 基于数据库最近一次价格生成模拟波动价格
 */
function generate_simulated_prices($pdo, $table, $ranges) {
    $prices = [];
    foreach ($ranges as $slug => $info) {
        try {
            $stmt = $pdo->prepare(
                "SELECT price FROM `{$table}` 
                 WHERE item_slug = ? 
                 ORDER BY pdate DESC LIMIT 1"
            );
            $stmt->execute([$slug]);
            $last = $stmt->fetchColumn();

            if ($last !== false) {
                // 在上次价格基础上随机波动 ±0.8%
                $change = $last * (mt_rand(-80, 80) / 10000);
                $new_price = round($last + $change, 2);
                // 边界保护
                $new_price = max($info['min'], min($info['max'], $new_price));
                log_msg("模拟价格：{$info['name']} = {$new_price} 元/吨（上次：{$last}）");
            } else {
                // 数据库中没有历史数据，使用默认初始价格
                $defaults = [
                    'urea'     => 1980.00,
                    'dap'      => 4366.00,
                    'mop'      => 3258.00,
                    'compound' => 3200.00,
                ];
                $new_price = $defaults[$slug] ?? (($info['min'] + $info['max']) / 2);
                log_msg("无历史数据，使用默认价格：{$info['name']} = {$new_price} 元/吨");
            }
            $prices[$slug] = $new_price;
        } catch (PDOException $e) {
            log_msg("获取 {$slug} 历史价格失败：" . $e->getMessage(), 'WARN');
        }
    }
    return $prices;
}

/**
 * 向 wp_fertp_price_points 表写入价格
 * 若当日已有记录则更新，否则插入新记录
 */
function insert_price($pdo, $table, $item_slug, $date, $price) {
    try {
        // 检查当日是否已有该品种的记录
        $check = $pdo->prepare(
            "SELECT id FROM `{$table}` WHERE item_slug = ? AND pdate = ?"
        );
        $check->execute([$item_slug, $date]);
        $existing_id = $check->fetchColumn();

        if ($existing_id) {
            // 更新已有记录
            $stmt = $pdo->prepare(
                "UPDATE `{$table}` SET price = ?, source = 'auto_script' WHERE id = ?"
            );
            return $stmt->execute([$price, $existing_id]);
        } else {
            // 插入新记录
            $stmt = $pdo->prepare(
                "INSERT INTO `{$table}` 
                 (item_slug, pdate, price, source, created_at) 
                 VALUES (?, ?, ?, 'auto_script', NOW())"
            );
            return $stmt->execute([$item_slug, $date, $price]);
        }
    } catch (PDOException $e) {
        log_msg('数据库写入异常：' . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 更新 wp_fertp_price_items 表的 updated_at 时间戳
 */
function update_item_timestamp($pdo, $table_items, $slug) {
    try {
        $stmt = $pdo->prepare(
            "UPDATE `{$table_items}` SET updated_at = NOW() WHERE slug = ?"
        );
        $stmt->execute([$slug]);
    } catch (PDOException $e) {
        // 非关键操作，仅记录警告
        log_msg("更新 items 时间戳失败（{$slug}）：" . $e->getMessage(), 'WARN');
    }
}
