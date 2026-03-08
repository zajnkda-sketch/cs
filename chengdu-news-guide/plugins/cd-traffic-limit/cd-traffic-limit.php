<?php
/**
 * Plugin Name:  CD Traffic Limit
 * Plugin URI:   https://your-domain.com/plugins/cd-traffic-limit
 * Description:  成都机动车尾号限行查询工具，支持今日限行查询、车牌尾号查询、近7天限行预览。
 * Version:      1.0.0
 * Author:       Your Name
 * Author URI:   https://your-domain.com
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  cd-traffic-limit
 */

if (!defined('ABSPATH')) exit;

// 加载核心类
require_once plugin_dir_path(__FILE__) . 'includes/class-traffic-limit.php';

// 初始化插件
new CD_Traffic_Limit();
