<?php
/**
 * Plugin Name:  CD Social Calculator
 * Plugin URI:   https://your-domain.com/plugins/cd-social-calculator
 * Description:  成都五险一金及个人所得税计算工具，基于成都市最新社保缴费标准，支持自定义缴纳比例和专项附加扣除。
 * Version:      1.0.0
 * Author:       Your Name
 * Author URI:   https://your-domain.com
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  cd-social-calculator
 */

if (!defined('ABSPATH')) exit;

// 加载核心类
require_once plugin_dir_path(__FILE__) . 'includes/class-calculator.php';

// 初始化插件
new CD_Social_Calculator();
