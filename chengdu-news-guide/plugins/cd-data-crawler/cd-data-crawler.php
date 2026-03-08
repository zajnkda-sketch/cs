<?php
/**
 * Plugin Name:  CD Data Crawler
 * Plugin URI:   https://your-domain.com/plugins/cd-data-crawler
 * Description:  成都本地资讯数据自动采集插件，支持配置多个采集任务，定时从官方网站采集并发布资讯内容。
 * Version:      1.0.0
 * Author:       Your Name
 * Author URI:   https://your-domain.com
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  cd-data-crawler
 */

if (!defined('ABSPATH')) exit;

// 加载核心类
require_once plugin_dir_path(__FILE__) . 'includes/class-crawler.php';

// 初始化插件
new CD_Data_Crawler();
