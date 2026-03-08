# 成都资讯门户网站 WordPress 开发指南

> 本指南面向希望基于 WordPress 构建类似"成都本地宝"的本地资讯门户网站的开发者，涵盖从系统架构设计、功能模块开发、实用查询工具集成到数据采集插件开发的完整技术方案。

**作者**：Manus AI  
**版本**：v1.0  
**更新日期**：2026 年 3 月  
**适用平台**：WordPress 6.x

---

## 目录

1. [项目概述](#1-项目概述)
2. [系统架构设计](#2-系统架构设计)
3. [服务器环境搭建](#3-服务器环境搭建)
4. [WordPress 核心配置](#4-wordpress-核心配置)
5. [主题开发规范](#5-主题开发规范)
6. [功能模块开发](#6-功能模块开发)
7. [实用查询工具开发](#7-实用查询工具开发)
8. [数据采集插件开发](#8-数据采集插件开发)
9. [SEO 与性能优化](#9-seo-与性能优化)
10. [安全与合规](#10-安全与合规)
11. [运维与监控](#11-运维与监控)
12. [参考资料](#12-参考资料)

---

## 1. 项目概述

### 1.1 网站定位

本项目旨在打造一个以成都本地生活服务为核心的综合资讯门户网站，参考"成都本地宝"的产品形态，为成都市民提供涵盖政务办事、交通出行、生活服务、实用查询等多维度的信息服务平台。网站的核心价值在于**信息的权威性、实用性和及时性**，通过整合官方数据源与自动化采集系统，实现内容的持续更新与精准推送。

### 1.2 核心功能模块

| 模块名称 | 功能描述 | 优先级 |
|---------|---------|--------|
| 资讯中心 | 成都本地新闻、民生动态、政策解读 | 高 |
| 政务办事 | 社保、公积金、户籍、出入境等办事指南 | 高 |
| 实用查询 | 限行查询、五险一金计算、违章查询等 | 高 |
| 交通出行 | 地铁、公交、限行、路况等出行信息 | 高 |
| 生活服务 | 医院挂号、驾考预约、住房信息等 | 中 |
| 旅游休闲 | 景点推荐、活动信息、周边游攻略 | 中 |
| 招聘就业 | 政府、国企、事业单位招聘信息 | 中 |
| 教育资讯 | 高考、中考、招生政策等教育信息 | 中 |

### 1.3 技术选型说明

选择 WordPress 作为核心框架的主要原因在于其成熟的内容管理体系、丰富的插件生态以及活跃的开发者社区。WordPress 的 REST API 支持前后端分离架构，可以在保留 CMS 优势的同时，灵活扩展自定义功能。结合自定义插件开发，可以实现限行查询、五险一金计算等特色功能，满足本地资讯门户的差异化需求。

---

## 2. 系统架构设计

### 2.1 整体架构图

```
┌─────────────────────────────────────────────────────────┐
│                      用户访问层                          │
│          PC浏览器 / 移动端浏览器 / 微信小程序            │
└─────────────────────────┬───────────────────────────────┘
                          │ HTTPS
┌─────────────────────────▼───────────────────────────────┐
│                    CDN / 负载均衡                         │
│              Nginx 反向代理 + SSL 终止                   │
└─────────────────────────┬───────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────┐
│                  WordPress 应用层                         │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │  主题层      │  │  插件层       │  │  REST API层    │  │
│  │ (前端展示)   │  │ (功能扩展)    │  │ (数据接口)     │  │
│  └─────────────┘  └──────────────┘  └────────────────┘  │
└─────────────────────────┬───────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────┐
│                     数据存储层                            │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  MySQL 8.0   │  │  Redis 缓存  │  │  文件存储      │  │
│  │  (主数据库)   │  │  (热点数据)  │  │  (媒体文件)   │  │
│  └──────────────┘  └──────────────┘  └───────────────┘  │
└─────────────────────────┬───────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────┐
│                   外部数据源层                            │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │  政府官网     │  │  第三方API   │  │  RSS 订阅源   │  │
│  │  (采集目标)   │  │  (限行/社保) │  │  (新闻资讯)   │  │
│  └──────────────┘  └──────────────┘  └───────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 2.2 数据库设计扩展

WordPress 默认数据库表可满足基本内容管理需求，但对于本地资讯门户的特色功能，需要扩展以下自定义数据表：

| 表名 | 用途 | 主要字段 |
|------|------|---------|
| `wp_cd_traffic_limit` | 成都限行规则数据 | `date`, `weekday`, `tail_numbers`, `area`, `time_range` |
| `wp_cd_social_insurance` | 社保缴费标准 | `year`, `type`, `personal_rate`, `company_rate`, `base_min`, `base_max` |
| `wp_cd_housing_fund` | 公积金标准 | `year`, `rate_min`, `rate_max`, `base_min`, `base_max` |
| `wp_cd_crawl_tasks` | 采集任务配置 | `task_name`, `source_url`, `selector_rules`, `frequency`, `status` |
| `wp_cd_crawl_logs` | 采集日志记录 | `task_id`, `crawl_time`, `items_count`, `status`, `error_msg` |

### 2.3 插件架构规划

本项目的自定义功能将通过以下插件模块化实现，每个插件职责单一、相互独立：

| 插件名称 | 文件目录 | 主要职责 |
|---------|---------|---------|
| CD Traffic Limit | `cd-traffic-limit/` | 成都限行查询功能 |
| CD Social Calculator | `cd-social-calculator/` | 五险一金计算工具 |
| CD Data Crawler | `cd-data-crawler/` | 官方数据自动采集 |
| CD Local Tools | `cd-local-tools/` | 其他实用工具集合 |
| CD News Aggregator | `cd-news-aggregator/` | 新闻资讯聚合发布 |

---

## 3. 服务器环境搭建

### 3.1 推荐服务器配置

根据网站规模和访问量，推荐以下服务器配置方案：

| 阶段 | CPU | 内存 | 存储 | 带宽 | 适用场景 |
|------|-----|------|------|------|---------|
| 初期（日PV < 1万） | 2核 | 4GB | 100GB SSD | 5Mbps | 测试上线阶段 |
| 成长期（日PV 1-10万） | 4核 | 8GB | 200GB SSD | 20Mbps | 稳定运营阶段 |
| 成熟期（日PV > 10万） | 8核+ | 16GB+ | 500GB SSD | 50Mbps+ | 规模化运营 |

**推荐云服务商**：阿里云、腾讯云、华为云（均提供成都区域节点，可降低访问延迟）

### 3.2 LNMP 环境安装

推荐使用 LNMP 一键安装包（Linux + Nginx + MySQL + PHP）快速搭建环境。

```bash
# 下载 LNMP 安装包（以 lnmp.org 为例）
wget http://soft.vpser.net/lnmp/lnmp2.0.tar.gz
tar -zxvf lnmp2.0.tar.gz
cd lnmp2.0

# 执行安装（选择 MySQL 8.0、PHP 8.2）
./install.sh lnmp
```

安装完成后，建议对 PHP 进行以下配置优化：

```ini
; /usr/local/php/etc/php.ini 关键配置
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_vars = 3000
```

### 3.3 Nginx 虚拟主机配置

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    # SSL 证书配置（建议使用 Let's Encrypt 免费证书）
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    root /home/wwwroot/your-domain.com;
    index index.php index.html;

    # WordPress 固定链接支持
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # 静态资源缓存
    location ~* \.(css|js|png|jpg|gif|ico|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # 禁止访问敏感文件
    location ~ /\.(ht|git) {
        deny all;
    }
}
```

### 3.4 Redis 缓存安装配置

```bash
# 安装 Redis
apt-get install redis-server -y

# 配置 Redis（/etc/redis/redis.conf）
# 设置最大内存为 512MB，使用 LRU 淘汰策略
maxmemory 512mb
maxmemory-policy allkeys-lru

# 安装 PHP Redis 扩展
pecl install redis
echo "extension=redis.so" >> /usr/local/php/etc/php.ini
```

---

## 4. WordPress 核心配置

### 4.1 安装与初始化

```bash
# 下载 WordPress 最新版本
wget https://cn.wordpress.org/latest-zh_CN.zip
unzip latest-zh_CN.zip -d /home/wwwroot/your-domain.com

# 配置数据库连接（wp-config.php）
cp wp-config-sample.php wp-config.php
```

在 `wp-config.php` 中配置以下关键参数：

```php
<?php
// 数据库配置
define('DB_NAME', 'chengdu_news');
define('DB_USER', 'wp_user');
define('DB_PASSWORD', 'your_strong_password');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');

// 安全密钥（从 https://api.wordpress.org/secret-key/1.1/salt/ 获取）
define('AUTH_KEY',         'your-unique-phrase-here');
define('SECURE_AUTH_KEY',  'your-unique-phrase-here');
define('LOGGED_IN_KEY',    'your-unique-phrase-here');
define('NONCE_KEY',        'your-unique-phrase-here');

// 性能优化
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// 禁用文件编辑（安全考虑）
define('DISALLOW_FILE_EDIT', true);

// 自动更新设置
define('WP_AUTO_UPDATE_CORE', 'minor');

// 表前缀（建议修改默认前缀）
$table_prefix = 'cd_';
```

### 4.2 必装插件清单

以下插件是构建本地资讯门户的基础组件：

| 插件名称 | 用途 | 安装方式 |
|---------|------|---------|
| Yoast SEO | SEO 优化与结构化数据 | 官方插件库 |
| WP Super Cache | 页面缓存加速 | 官方插件库 |
| Redis Object Cache | Redis 对象缓存 | 官方插件库 |
| Advanced Custom Fields (ACF) | 自定义字段扩展 | 官方插件库 |
| WP Crontrol | 定时任务管理 | 官方插件库 |
| Wordfence Security | 安全防护 | 官方插件库 |
| WP-Optimize | 数据库优化清理 | 官方插件库 |
| TablePress | 表格展示 | 官方插件库 |
| Contact Form 7 | 用户反馈表单 | 官方插件库 |
| Smush | 图片压缩优化 | 官方插件库 |

### 4.3 自定义文章类型注册

本地资讯门户需要注册多种自定义文章类型（Custom Post Type）来管理不同类别的内容：

```php
<?php
// 在主题的 functions.php 或自定义插件中添加

function cd_register_custom_post_types() {

    // 注册"办事指南"文章类型
    register_post_type('cd_guide', [
        'labels' => [
            'name'          => '办事指南',
            'singular_name' => '指南',
            'add_new_item'  => '添加新指南',
            'edit_item'     => '编辑指南',
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-book',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'rewrite'      => ['slug' => 'guide'],
        'show_in_rest' => true, // 支持 REST API
    ]);

    // 注册"活动信息"文章类型
    register_post_type('cd_event', [
        'labels' => [
            'name'          => '活动信息',
            'singular_name' => '活动',
            'add_new_item'  => '添加新活动',
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-calendar',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'rewrite'      => ['slug' => 'event'],
        'show_in_rest' => true,
    ]);

    // 注册"招聘信息"文章类型
    register_post_type('cd_job', [
        'labels' => [
            'name'          => '招聘信息',
            'singular_name' => '招聘',
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-businessman',
        'supports'     => ['title', 'editor', 'custom-fields'],
        'rewrite'      => ['slug' => 'job'],
        'show_in_rest' => true,
    ]);
}

add_action('init', 'cd_register_custom_post_types');
```

---

## 5. 主题开发规范

### 5.1 主题目录结构

```
chengdu-news-theme/
├── style.css                 # 主题样式表（包含主题信息头部）
├── functions.php             # 主题功能函数
├── index.php                 # 主模板文件
├── header.php                # 页头模板
├── footer.php                # 页脚模板
├── sidebar.php               # 侧边栏模板
├── front-page.php            # 首页模板
├── single.php                # 文章详情模板
├── archive.php               # 归档页模板
├── search.php                # 搜索结果模板
├── 404.php                   # 404 错误页模板
├── page-templates/
│   ├── page-tools.php        # 工具页面模板
│   ├── page-guide.php        # 办事指南模板
│   └── page-traffic.php      # 交通信息模板
├── template-parts/
│   ├── content-news.php      # 新闻内容片段
│   ├── content-guide.php     # 指南内容片段
│   ├── widget-tools.php      # 工具小组件
│   └── nav-categories.php    # 分类导航片段
├── assets/
│   ├── css/
│   │   ├── main.css          # 主样式文件
│   │   └── responsive.css    # 响应式样式
│   ├── js/
│   │   ├── main.js           # 主脚本文件
│   │   └── tools.js          # 工具类脚本
│   └── images/               # 主题图片资源
└── inc/
    ├── customizer.php        # 主题定制器
    ├── widgets.php           # 自定义小工具
    └── helpers.php           # 辅助函数
```

### 5.2 响应式设计要点

成都资讯门户的移动端访问量通常占总访问量的 60%-70%，因此响应式设计至关重要。建议采用移动优先（Mobile First）的设计策略：

```css
/* 移动端基础样式（默认） */
.news-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    padding: 16px;
}

/* 平板端（768px 以上） */
@media (min-width: 768px) {
    .news-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        padding: 20px;
    }
}

/* 桌面端（1024px 以上） */
@media (min-width: 1024px) {
    .news-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
}

/* 宽屏（1440px 以上） */
@media (min-width: 1440px) {
    .container {
        max-width: 1400px;
        margin: 0 auto;
    }
}
```

---

## 6. 功能模块开发

### 6.1 资讯分类体系

建议按照以下层级结构建立内容分类体系，以便于 SEO 优化和用户导航：

```
成都资讯（根分类）
├── 政务民生
│   ├── 社保公积金
│   ├── 户籍办理
│   ├── 出入境
│   └── 政策法规
├── 交通出行
│   ├── 限行信息
│   ├── 地铁公交
│   └── 路况信息
├── 生活服务
│   ├── 医疗健康
│   ├── 住房信息
│   └── 教育资讯
├── 招聘就业
│   ├── 政府招聘
│   ├── 国企招聘
│   └── 事业单位
└── 休闲娱乐
    ├── 景点旅游
    ├── 活动展览
    └── 美食推荐
```

### 6.2 首页布局设计

首页应采用信息密度适中的布局，参考成都本地宝的设计，建议包含以下区域：

```php
<?php
// front-page.php 首页模板结构

get_header();
?>

<!-- 顶部快捷导航 -->
<div class="quick-nav">
    <?php cd_render_quick_nav(); ?>
</div>

<!-- 今日限行提示横幅 -->
<div class="traffic-banner">
    <?php cd_render_today_traffic_limit(); ?>
</div>

<!-- 主要内容区域（三栏布局） -->
<div class="main-content">
    <!-- 左侧：焦点资讯 -->
    <div class="col-left">
        <?php cd_render_featured_news(); ?>
    </div>

    <!-- 中间：最新资讯列表 -->
    <div class="col-center">
        <?php cd_render_latest_news(); ?>
    </div>

    <!-- 右侧：城市服务工具 -->
    <div class="col-right">
        <?php cd_render_city_services(); ?>
        <?php cd_render_quick_tools(); ?>
    </div>
</div>

<!-- 专题推荐区域 -->
<div class="featured-topics">
    <?php cd_render_featured_topics(); ?>
</div>

<?php get_footer(); ?>
```

### 6.3 搜索功能增强

WordPress 默认搜索功能较弱，建议集成 Elasticsearch 或使用 SearchWP 插件增强搜索体验：

```php
<?php
// 自定义搜索过滤器，支持按分类、时间范围搜索

function cd_enhanced_search_query($query) {
    if ($query->is_search() && !is_admin()) {
        // 支持按文章类型过滤
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'any';
        $query->set('post_type', $post_type);

        // 支持按时间范围过滤
        if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
            $query->set('date_query', [
                [
                    'after'     => sanitize_text_field($_GET['date_from']),
                    'before'    => sanitize_text_field($_GET['date_to']),
                    'inclusive' => true,
                ],
            ]);
        }

        // 按相关度排序
        $query->set('orderby', 'relevance');
    }
    return $query;
}

add_filter('pre_get_posts', 'cd_enhanced_search_query');
```

---

## 7. 实用查询工具开发

### 7.1 成都限行查询工具

限行查询是本地资讯门户的核心差异化功能之一。实现方案分为两种：**规则计算法**（适合成都固定轮换限行）和 **API 调用法**（适合复杂限行规则）。

#### 7.1.1 限行规则数据库设计

```sql
-- 创建限行规则表
CREATE TABLE wp_cd_traffic_limit (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    date        DATE NOT NULL COMMENT '限行日期',
    weekday     TINYINT NOT NULL COMMENT '星期几（1-7）',
    tail_numbers VARCHAR(20) NOT NULL COMMENT '限行尾号（如：1,6）',
    area        TEXT NOT NULL COMMENT '限行区域描述',
    time_range  VARCHAR(50) NOT NULL COMMENT '限行时间段',
    vehicle_type VARCHAR(100) COMMENT '限行车辆类型',
    is_holiday  TINYINT DEFAULT 0 COMMENT '是否节假日（节假日不限行）',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_weekday (weekday)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='成都限行规则表';
```

#### 7.1.2 限行查询插件核心代码

```php
<?php
/**
 * Plugin Name: CD Traffic Limit
 * Plugin URI:  https://your-domain.com
 * Description: 成都机动车尾号限行查询工具
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL2
 */

if (!defined('ABSPATH')) exit;

class CD_Traffic_Limit {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('cd_traffic_limit', [$this, 'render_shortcode']);
        add_action('wp_ajax_cd_query_traffic', [$this, 'ajax_query_traffic']);
        add_action('wp_ajax_nopriv_cd_query_traffic', [$this, 'ajax_query_traffic']);
        add_action('wp_ajax_cd_check_plate', [$this, 'ajax_check_plate']);
        add_action('wp_ajax_nopriv_cd_check_plate', [$this, 'ajax_check_plate']);
    }

    /**
     * 加载前端资源
     */
    public function enqueue_scripts() {
        wp_enqueue_style('cd-traffic-limit', plugin_dir_url(__FILE__) . 'assets/css/traffic-limit.css');
        wp_enqueue_script('cd-traffic-limit', plugin_dir_url(__FILE__) . 'assets/js/traffic-limit.js', ['jquery'], '1.0.0', true);
        wp_localize_script('cd-traffic-limit', 'cdTrafficLimit', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cd_traffic_nonce'),
        ]);
    }

    /**
     * 短代码渲染：在页面中嵌入限行查询工具
     * 使用方式：[cd_traffic_limit]
     */
    public function render_shortcode($atts) {
        $today_limit = $this->get_today_limit();
        ob_start();
        ?>
        <div class="cd-traffic-limit-widget">
            <!-- 今日限行信息展示 -->
            <div class="today-limit-info">
                <h3>今日限行信息</h3>
                <?php if ($today_limit): ?>
                <div class="limit-details">
                    <div class="limit-item">
                        <span class="label">限行尾号</span>
                        <span class="value highlight"><?php echo esc_html($today_limit->tail_numbers); ?></span>
                    </div>
                    <div class="limit-item">
                        <span class="label">限行时间</span>
                        <span class="value"><?php echo esc_html($today_limit->time_range); ?></span>
                    </div>
                    <div class="limit-item">
                        <span class="label">限行区域</span>
                        <span class="value"><?php echo esc_html($today_limit->area); ?></span>
                    </div>
                </div>
                <?php else: ?>
                <p class="no-limit">今日无限行（节假日或数据暂未更新）</p>
                <?php endif; ?>
            </div>

            <!-- 车牌尾号查询 -->
            <div class="plate-query-form">
                <h3>查询我的车牌是否限行</h3>
                <div class="form-group">
                    <input type="text" id="plate-number" placeholder="请输入车牌号（如：川A12345）" maxlength="8">
                    <button id="check-plate-btn" class="btn-primary">查询</button>
                </div>
                <div id="plate-query-result" class="query-result"></div>
            </div>

            <!-- 未来7天限行预览 -->
            <div class="weekly-limit">
                <h3>近7天限行安排</h3>
                <div id="weekly-limit-table">
                    <?php echo $this->render_weekly_limit(); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 获取今日限行信息
     */
    public function get_today_limit() {
        global $wpdb;
        $today = current_time('Y-m-d');

        // 优先从缓存获取
        $cache_key = 'cd_traffic_limit_' . $today;
        $cached = wp_cache_get($cache_key);
        if ($cached !== false) return $cached;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cd_traffic_limit WHERE date = %s AND is_holiday = 0",
            $today
        ));

        // 如果数据库中没有，则根据规则计算
        if (!$result) {
            $result = $this->calculate_limit_by_rule($today);
        }

        wp_cache_set($cache_key, $result, '', 3600); // 缓存1小时
        return $result;
    }

    /**
     * 根据成都限行轮换规则计算限行尾号
     * 成都限行规则：工作日按星期一至五轮换，每周限行尾号固定
     */
    public function calculate_limit_by_rule($date) {
        $timestamp = strtotime($date);
        $weekday   = date('N', $timestamp); // 1=周一, 7=周日

        // 周末不限行
        if ($weekday >= 6) return null;

        // 成都限行尾号轮换规则（以某周为基准，每周循环）
        // 实际规则需根据成都交管局最新公告更新
        $limit_rules = [
            1 => '1,6',  // 周一限行尾号1和6
            2 => '2,7',  // 周二限行尾号2和7
            3 => '3,8',  // 周三限行尾号3和8
            4 => '4,9',  // 周四限行尾号4和9
            5 => '5,0',  // 周五限行尾号5和0
        ];

        // 计算当前是第几周（用于处理轮换）
        $base_date  = strtotime('2024-01-01'); // 基准日期
        $week_diff  = floor(($timestamp - $base_date) / (7 * 86400));
        $week_cycle = $week_diff % 5; // 5周一个大循环

        // 根据轮换周期调整尾号
        $adjusted_weekday = (($weekday - 1 + $week_cycle) % 5) + 1;
        $tail_numbers     = $limit_rules[$adjusted_weekday] ?? '1,6';

        return (object) [
            'date'         => $date,
            'weekday'      => $weekday,
            'tail_numbers' => $tail_numbers,
            'area'         => '成都市绕城高速公路（G4202）以内所有道路',
            'time_range'   => '7:30 - 20:00',
            'vehicle_type' => '川A/川G及外地籍小型、微型载客汽车（新能源车辆不限行）',
            'is_holiday'   => 0,
        ];
    }

    /**
     * AJAX 处理：查询指定日期的限行信息
     */
    public function ajax_query_traffic() {
        check_ajax_referer('cd_traffic_nonce', 'nonce');

        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
        $limit = $this->get_limit_by_date($date);

        if ($limit) {
            wp_send_json_success([
                'date'         => $date,
                'tail_numbers' => $limit->tail_numbers,
                'time_range'   => $limit->time_range,
                'area'         => $limit->area,
                'vehicle_type' => $limit->vehicle_type,
            ]);
        } else {
            wp_send_json_success(['message' => '该日期无限行限制（节假日或周末）']);
        }
    }

    /**
     * AJAX 处理：检查车牌是否限行
     */
    public function ajax_check_plate() {
        check_ajax_referer('cd_traffic_nonce', 'nonce');

        $plate = isset($_POST['plate']) ? strtoupper(sanitize_text_field($_POST['plate'])) : '';

        if (empty($plate) || strlen($plate) < 7) {
            wp_send_json_error('请输入有效的车牌号');
        }

        // 提取车牌尾号（最后一位数字）
        $tail = substr($plate, -1);
        if (!is_numeric($tail)) {
            // 尾号为字母时，按特殊规则处理（成都规定字母尾号不限行）
            wp_send_json_success(['is_limited' => false, 'message' => '您的车牌尾号为字母，不受尾号限行限制']);
        }

        $today_limit = $this->get_today_limit();
        if (!$today_limit) {
            wp_send_json_success(['is_limited' => false, 'message' => '今日无限行限制']);
        }

        $limited_tails = explode(',', $today_limit->tail_numbers);
        $is_limited    = in_array($tail, $limited_tails);

        wp_send_json_success([
            'is_limited'   => $is_limited,
            'tail'         => $tail,
            'tail_numbers' => $today_limit->tail_numbers,
            'time_range'   => $today_limit->time_range,
            'message'      => $is_limited
                ? "您的车牌尾号为 {$tail}，今日（{$today_limit->time_range}）在限行区域内限行"
                : "您的车牌尾号为 {$tail}，今日不在限行范围内",
        ]);
    }

    /**
     * 渲染近7天限行表格
     */
    private function render_weekly_limit() {
        $html = '<table class="weekly-limit-table"><thead><tr><th>日期</th><th>星期</th><th>限行尾号</th><th>状态</th></tr></thead><tbody>';
        $weekday_names = ['', '周一', '周二', '周三', '周四', '周五', '周六', '周日'];

        for ($i = 0; $i < 7; $i++) {
            $date      = date('Y-m-d', strtotime("+{$i} days"));
            $weekday   = date('N', strtotime($date));
            $limit     = $this->calculate_limit_by_rule($date);
            $is_today  = ($i === 0) ? ' class="today"' : '';

            $html .= "<tr{$is_today}>";
            $html .= "<td>" . date('m/d', strtotime($date)) . "</td>";
            $html .= "<td>" . $weekday_names[$weekday] . "</td>";
            $html .= "<td>" . ($limit ? esc_html($limit->tail_numbers) : '不限行') . "</td>";
            $html .= "<td>" . ($limit ? '<span class="status-limited">限行</span>' : '<span class="status-free">不限行</span>') . "</td>";
            $html .= "</tr>";
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function get_limit_by_date($date) {
        return $this->calculate_limit_by_rule($date);
    }
}

new CD_Traffic_Limit();
```

### 7.2 五险一金计算工具

五险一金计算工具需要根据成都最新的缴费标准进行精确计算，并支持用户自定义参数。

#### 7.2.1 计算工具插件核心代码

```php
<?php
/**
 * Plugin Name: CD Social Calculator
 * Description: 成都五险一金及个税计算工具
 * Version:     1.0.0
 */

if (!defined('ABSPATH')) exit;

class CD_Social_Calculator {

    /**
     * 成都2025年社保缴费标准（2025年10月1日起执行）
     */
    private $chengdu_rates = [
        'year'         => 2025,
        'base_min'     => 4588,   // 缴费基数下限（元/月）
        'base_max'     => 22938,  // 缴费基数上限（元/月）
        'gjj_base_max' => 31362,  // 公积金基数上限（元/月）
        'insurance' => [
            'pension' => [
                'name'         => '养老保险',
                'personal'     => 0.08,   // 个人8%
                'company'      => 0.19,   // 单位19%
            ],
            'medical' => [
                'name'         => '医疗保险',
                'personal'     => 0.02,   // 个人2%
                'company'      => 0.0675, // 单位6.75%（含生育险0.8%）
            ],
            'unemployment' => [
                'name'         => '失业保险',
                'personal'     => 0.004,  // 个人0.4%
                'company'      => 0.006,  // 单位0.6%
            ],
            'injury' => [
                'name'         => '工伤保险',
                'personal'     => 0,      // 个人不缴
                'company'      => 0.005,  // 单位0.5%（按行业，取最低）
            ],
        ],
        'housing_fund' => [
            'name'         => '住房公积金',
            'rate_min'     => 0.05,  // 最低5%
            'rate_max'     => 0.12,  // 最高12%
            'default_rate' => 0.12,  // 默认12%
        ],
    ];

    public function __construct() {
        add_shortcode('cd_social_calculator', [$this, 'render_shortcode']);
        add_action('wp_ajax_cd_calculate_social', [$this, 'ajax_calculate']);
        add_action('wp_ajax_nopriv_cd_calculate_social', [$this, 'ajax_calculate']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('cd-social-calc', plugin_dir_url(__FILE__) . 'assets/css/social-calc.css');
        wp_enqueue_script('cd-social-calc', plugin_dir_url(__FILE__) . 'assets/js/social-calc.js', ['jquery'], '1.0.0', true);
        wp_localize_script('cd-social-calc', 'cdSocialCalc', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('cd_social_nonce'),
            'rates'   => $this->chengdu_rates,
        ]);
    }

    /**
     * 短代码渲染：[cd_social_calculator]
     */
    public function render_shortcode() {
        ob_start();
        ?>
        <div class="cd-social-calculator">
            <h2>成都五险一金计算器</h2>
            <p class="calc-note">数据基于成都市2025年社保缴费标准（2025年10月1日起执行）</p>

            <div class="calc-form">
                <div class="form-row">
                    <label>税前月工资（元）</label>
                    <input type="number" id="gross-salary" placeholder="请输入税前月工资" min="0" step="100">
                </div>
                <div class="form-row">
                    <label>社保缴费基数（元，留空则按工资计算）</label>
                    <input type="number" id="social-base" placeholder="默认按实际工资计算">
                    <span class="hint">基数范围：4,588 ~ 22,938 元/月</span>
                </div>
                <div class="form-row">
                    <label>公积金缴纳比例</label>
                    <select id="gjj-rate">
                        <option value="0.05">5%</option>
                        <option value="0.06">6%</option>
                        <option value="0.07">7%</option>
                        <option value="0.08">8%</option>
                        <option value="0.09">9%</option>
                        <option value="0.10">10%</option>
                        <option value="0.11">11%</option>
                        <option value="0.12" selected>12%</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>专项附加扣除（元/月）</label>
                    <input type="number" id="special-deduction" placeholder="子女教育、住房贷款等" min="0">
                </div>
                <button id="calc-btn" class="btn-primary">立即计算</button>
            </div>

            <div id="calc-result" class="calc-result" style="display:none;">
                <h3>计算结果</h3>
                <div class="result-summary">
                    <div class="result-item highlight">
                        <span class="label">实发工资（税后）</span>
                        <span class="value" id="net-salary">-</span>
                    </div>
                    <div class="result-item">
                        <span class="label">个人缴纳五险一金合计</span>
                        <span class="value" id="personal-total">-</span>
                    </div>
                    <div class="result-item">
                        <span class="label">单位缴纳五险一金合计</span>
                        <span class="value" id="company-total">-</span>
                    </div>
                    <div class="result-item">
                        <span class="label">应缴个人所得税</span>
                        <span class="value" id="income-tax">-</span>
                    </div>
                </div>
                <table class="result-detail-table">
                    <thead>
                        <tr><th>项目</th><th>缴费基数</th><th>个人比例</th><th>个人缴纳</th><th>单位比例</th><th>单位缴纳</th></tr>
                    </thead>
                    <tbody id="result-detail-body"></tbody>
                </table>
                <p class="result-disclaimer">* 以上计算结果仅供参考，实际以社保局核定为准。工伤保险按0.5%最低档计算。</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX 处理：执行五险一金计算
     */
    public function ajax_calculate() {
        check_ajax_referer('cd_social_nonce', 'nonce');

        $gross_salary      = floatval($_POST['gross_salary'] ?? 0);
        $social_base_input = floatval($_POST['social_base'] ?? 0);
        $gjj_rate          = floatval($_POST['gjj_rate'] ?? 0.12);
        $special_deduction = floatval($_POST['special_deduction'] ?? 0);

        if ($gross_salary <= 0) {
            wp_send_json_error('请输入有效的工资金额');
        }

        $rates = $this->chengdu_rates;

        // 确定社保缴费基数
        $social_base = $social_base_input > 0 ? $social_base_input : $gross_salary;
        $social_base = max($rates['base_min'], min($rates['base_max'], $social_base));

        // 确定公积金缴费基数
        $gjj_base = min($rates['gjj_base_max'], $gross_salary);
        $gjj_rate = max(0.05, min(0.12, $gjj_rate));

        $details        = [];
        $personal_total = 0;
        $company_total  = 0;

        // 计算各险种
        foreach ($rates['insurance'] as $key => $insurance) {
            $personal_amount = round($social_base * $insurance['personal'], 2);
            $company_amount  = round($social_base * $insurance['company'], 2);
            $personal_total += $personal_amount;
            $company_total  += $company_amount;

            $details[] = [
                'name'           => $insurance['name'],
                'base'           => $social_base,
                'personal_rate'  => $insurance['personal'] * 100 . '%',
                'personal_amount'=> $personal_amount,
                'company_rate'   => $insurance['company'] * 100 . '%',
                'company_amount' => $company_amount,
            ];
        }

        // 计算公积金
        $gjj_personal = round($gjj_base * $gjj_rate, 2);
        $gjj_company  = round($gjj_base * $gjj_rate, 2);
        $personal_total += $gjj_personal;
        $company_total  += $gjj_company;

        $details[] = [
            'name'           => '住房公积金',
            'base'           => $gjj_base,
            'personal_rate'  => ($gjj_rate * 100) . '%',
            'personal_amount'=> $gjj_personal,
            'company_rate'   => ($gjj_rate * 100) . '%',
            'company_amount' => $gjj_company,
        ];

        // 计算个人所得税（2024年标准）
        $taxable_income = $gross_salary - $personal_total - 5000 - $special_deduction; // 5000元起征点
        $income_tax     = $this->calculate_income_tax($taxable_income);

        // 计算实发工资
        $net_salary = $gross_salary - $personal_total - $income_tax;

        wp_send_json_success([
            'gross_salary'   => $gross_salary,
            'net_salary'     => round($net_salary, 2),
            'personal_total' => round($personal_total, 2),
            'company_total'  => round($company_total, 2),
            'income_tax'     => round($income_tax, 2),
            'details'        => $details,
        ]);
    }

    /**
     * 计算个人所得税（综合所得适用税率表）
     */
    private function calculate_income_tax($taxable_income) {
        if ($taxable_income <= 0) return 0;

        // 月应纳税所得额对应税率表（年化后按月计算）
        $tax_brackets = [
            [36000,   0.03, 0],
            [144000,  0.10, 2520],
            [300000,  0.20, 16920],
            [420000,  0.25, 31920],
            [660000,  0.30, 52920],
            [960000,  0.35, 85920],
            [PHP_INT_MAX, 0.45, 181920],
        ];

        // 转为年应纳税所得额计算
        $annual_taxable = $taxable_income * 12;

        foreach ($tax_brackets as $bracket) {
            if ($annual_taxable <= $bracket[0]) {
                return round(($annual_taxable * $bracket[1] - $bracket[2]) / 12, 2);
            }
        }

        return 0;
    }
}

new CD_Social_Calculator();
```

### 7.3 其他实用查询工具

除限行查询和五险一金计算外，还可以集成以下实用工具，通过短代码方式嵌入页面：

| 工具名称 | 短代码 | 实现方式 |
|---------|--------|---------|
| 违章查询 | `[cd_violation_query]` | 跳转成都交管局官方查询页面 |
| 公积金计算 | `[cd_gjj_calculator]` | 本地计算，参数可配置 |
| 个税计算器 | `[cd_income_tax_calc]` | 本地计算，支持专项扣除 |
| 社保查询入口 | `[cd_social_query]` | 跳转官方社保查询系统 |
| 医院挂号 | `[cd_hospital_booking]` | 集成成都市统一预约挂号平台 |
| 天气查询 | `[cd_weather]` | 调用和风天气或心知天气 API |
| 油价查询 | `[cd_oil_price]` | 调用第三方油价 API |
| 公交查询 | `[cd_bus_query]` | 集成高德地图公交 API |

---

## 8. 数据采集插件开发

### 8.1 采集系统架构

数据采集系统是本地资讯门户保持内容时效性的核心机制。整体架构分为三层：**任务调度层**（WordPress Cron）、**采集执行层**（PHP cURL + DOM 解析）和**数据处理层**（清洗、去重、存储）。

```
任务调度层（WordPress Cron）
    ↓ 触发采集任务
采集执行层（cURL 请求 + DOM 解析）
    ↓ 获取原始数据
数据处理层（清洗 + 去重 + 分类）
    ↓ 存储处理后的数据
WordPress 内容层（文章/自定义内容）
    ↓ 展示给用户
前端页面
```

### 8.2 采集插件核心代码

```php
<?php
/**
 * Plugin Name: CD Data Crawler
 * Description: 成都本地资讯数据自动采集插件
 * Version:     1.0.0
 */

if (!defined('ABSPATH')) exit;

class CD_Data_Crawler {

    private $table_tasks;
    private $table_logs;

    public function __construct() {
        global $wpdb;
        $this->table_tasks = $wpdb->prefix . 'cd_crawl_tasks';
        $this->table_logs  = $wpdb->prefix . 'cd_crawl_logs';

        register_activation_hook(__FILE__, [$this, 'create_tables']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('cd_crawl_cron', [$this, 'run_scheduled_crawl']);
        add_action('wp_ajax_cd_run_crawl', [$this, 'ajax_run_crawl']);
        add_action('wp_ajax_cd_save_task', [$this, 'ajax_save_task']);
        add_action('wp_ajax_cd_delete_task', [$this, 'ajax_delete_task']);

        // 注册定时任务（每小时执行一次）
        if (!wp_next_scheduled('cd_crawl_cron')) {
            wp_schedule_event(time(), 'hourly', 'cd_crawl_cron');
        }
    }

    /**
     * 创建数据库表
     */
    public function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql_tasks = "CREATE TABLE IF NOT EXISTS {$this->table_tasks} (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            task_name    VARCHAR(100) NOT NULL COMMENT '任务名称',
            source_url   TEXT NOT NULL COMMENT '采集源URL',
            list_selector VARCHAR(200) COMMENT '列表项CSS选择器',
            title_selector VARCHAR(200) COMMENT '标题CSS选择器',
            content_selector VARCHAR(200) COMMENT '正文CSS选择器',
            date_selector VARCHAR(200) COMMENT '日期CSS选择器',
            link_selector VARCHAR(200) COMMENT '链接CSS选择器',
            post_category INT DEFAULT 0 COMMENT '发布到的分类ID',
            post_status  VARCHAR(20) DEFAULT 'draft' COMMENT '发布状态',
            frequency    VARCHAR(20) DEFAULT 'hourly' COMMENT '采集频率',
            is_active    TINYINT DEFAULT 1 COMMENT '是否启用',
            last_crawl   DATETIME COMMENT '最后采集时间',
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) {$charset};";

        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->table_logs} (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            task_id      INT NOT NULL COMMENT '任务ID',
            crawl_time   DATETIME NOT NULL COMMENT '采集时间',
            items_found  INT DEFAULT 0 COMMENT '发现条目数',
            items_saved  INT DEFAULT 0 COMMENT '保存条目数',
            status       VARCHAR(20) DEFAULT 'success' COMMENT '状态',
            error_msg    TEXT COMMENT '错误信息',
            INDEX idx_task_id (task_id),
            INDEX idx_crawl_time (crawl_time)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_tasks);
        dbDelta($sql_logs);
    }

    /**
     * 添加后台管理菜单
     */
    public function add_admin_menu() {
        add_menu_page(
            '数据采集管理',
            '数据采集',
            'manage_options',
            'cd-data-crawler',
            [$this, 'render_admin_page'],
            'dashicons-download',
            30
        );
    }

    /**
     * 渲染后台管理页面
     */
    public function render_admin_page() {
        global $wpdb;
        $tasks = $wpdb->get_results("SELECT * FROM {$this->table_tasks} ORDER BY id DESC");
        ?>
        <div class="wrap">
            <h1>成都资讯数据采集管理</h1>

            <!-- 添加新任务表单 -->
            <div class="card" style="max-width:800px; padding:20px; margin-bottom:20px;">
                <h2>添加采集任务</h2>
                <table class="form-table">
                    <tr>
                        <th>任务名称</th>
                        <td><input type="text" id="task-name" class="regular-text" placeholder="如：成都交管局限行公告"></td>
                    </tr>
                    <tr>
                        <th>采集URL</th>
                        <td><input type="url" id="source-url" class="large-text" placeholder="https://example.gov.cn/news/"></td>
                    </tr>
                    <tr>
                        <th>列表项选择器</th>
                        <td><input type="text" id="list-selector" class="regular-text" placeholder=".news-list li"></td>
                    </tr>
                    <tr>
                        <th>标题选择器</th>
                        <td><input type="text" id="title-selector" class="regular-text" placeholder=".news-title a"></td>
                    </tr>
                    <tr>
                        <th>链接选择器</th>
                        <td><input type="text" id="link-selector" class="regular-text" placeholder=".news-title a[href]"></td>
                    </tr>
                    <tr>
                        <th>日期选择器</th>
                        <td><input type="text" id="date-selector" class="regular-text" placeholder=".news-date"></td>
                    </tr>
                    <tr>
                        <th>发布分类</th>
                        <td><?php wp_dropdown_categories(['name' => 'post-category', 'id' => 'post-category', 'show_option_none' => '-- 选择分类 --']); ?></td>
                    </tr>
                    <tr>
                        <th>发布状态</th>
                        <td>
                            <select id="post-status">
                                <option value="draft">草稿（需人工审核）</option>
                                <option value="publish">直接发布</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>采集频率</th>
                        <td>
                            <select id="frequency">
                                <option value="hourly">每小时</option>
                                <option value="twicedaily">每12小时</option>
                                <option value="daily">每天</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <button id="save-task-btn" class="button button-primary">保存任务</button>
            </div>

            <!-- 任务列表 -->
            <h2>采集任务列表</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>任务名称</th>
                        <th>采集源</th>
                        <th>频率</th>
                        <th>最后采集</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo esc_html($task->task_name); ?></td>
                        <td><a href="<?php echo esc_url($task->source_url); ?>" target="_blank"><?php echo esc_html(substr($task->source_url, 0, 50)) . '...'; ?></a></td>
                        <td><?php echo esc_html($task->frequency); ?></td>
                        <td><?php echo $task->last_crawl ? esc_html($task->last_crawl) : '从未执行'; ?></td>
                        <td><?php echo $task->is_active ? '<span style="color:green">启用</span>' : '<span style="color:red">停用</span>'; ?></td>
                        <td>
                            <button class="button run-crawl-btn" data-id="<?php echo $task->id; ?>">立即采集</button>
                            <button class="button delete-task-btn" data-id="<?php echo $task->id; ?>">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * 执行采集任务（核心方法）
     */
    public function execute_crawl_task($task) {
        $start_time   = microtime(true);
        $items_found  = 0;
        $items_saved  = 0;
        $error_msg    = '';

        try {
            // 1. 获取页面内容
            $html = $this->fetch_url($task->source_url);
            if (!$html) {
                throw new Exception("无法获取页面内容：{$task->source_url}");
            }

            // 2. 解析 HTML
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);

            // 3. 提取列表项（将 CSS 选择器转换为 XPath）
            $list_xpath = $this->css_to_xpath($task->list_selector);
            $list_nodes = $xpath->query($list_xpath);

            if (!$list_nodes || $list_nodes->length === 0) {
                throw new Exception("未找到列表项，请检查选择器：{$task->list_selector}");
            }

            $items_found = $list_nodes->length;

            // 4. 遍历每个列表项，提取数据
            foreach ($list_nodes as $node) {
                $item_dom   = new DOMDocument();
                $item_dom->appendChild($item_dom->importNode($node, true));
                $item_xpath = new DOMXPath($item_dom);

                // 提取标题
                $title_nodes = $item_xpath->query($this->css_to_xpath($task->title_selector));
                $title       = $title_nodes->length > 0 ? trim($title_nodes->item(0)->textContent) : '';

                // 提取链接
                $link_nodes = $item_xpath->query($this->css_to_xpath($task->link_selector));
                $link       = '';
                if ($link_nodes->length > 0) {
                    $href = $link_nodes->item(0)->getAttribute('href');
                    $link = $this->resolve_url($href, $task->source_url);
                }

                // 提取日期
                $date_nodes = $item_xpath->query($this->css_to_xpath($task->date_selector));
                $date       = $date_nodes->length > 0 ? trim($date_nodes->item(0)->textContent) : current_time('Y-m-d');

                if (empty($title)) continue;

                // 5. 检查是否已存在（去重）
                if ($this->post_exists($title, $link)) continue;

                // 6. 如果有详情链接，进一步采集正文
                $content = '';
                if (!empty($link) && !empty($task->content_selector)) {
                    $content = $this->fetch_content($link, $task->content_selector);
                    sleep(1); // 礼貌延迟，避免对目标服务器造成压力
                }

                // 7. 创建 WordPress 文章
                $post_id = wp_insert_post([
                    'post_title'    => sanitize_text_field($title),
                    'post_content'  => wp_kses_post($content),
                    'post_status'   => $task->post_status,
                    'post_category' => [$task->post_category],
                    'post_date'     => $this->parse_date($date),
                    'meta_input'    => [
                        '_crawl_source_url' => $link,
                        '_crawl_task_id'    => $task->id,
                        '_crawl_time'       => current_time('mysql'),
                    ],
                ]);

                if ($post_id && !is_wp_error($post_id)) {
                    $items_saved++;
                }
            }

        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }

        // 8. 记录采集日志
        $this->log_crawl($task->id, $items_found, $items_saved, $error_msg);

        // 9. 更新最后采集时间
        global $wpdb;
        $wpdb->update($this->table_tasks, ['last_crawl' => current_time('mysql')], ['id' => $task->id]);

        return [
            'items_found' => $items_found,
            'items_saved' => $items_saved,
            'error'       => $error_msg,
            'time'        => round(microtime(true) - $start_time, 2),
        ];
    }

    /**
     * 使用 cURL 获取页面内容
     */
    private function fetch_url($url, $timeout = 30) {
        $args = [
            'timeout'    => $timeout,
            'user-agent' => 'Mozilla/5.0 (compatible; ChengduNewsBot/1.0; +https://your-domain.com/bot)',
            'headers'    => [
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'zh-CN,zh;q=0.9',
            ],
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * 采集详情页正文内容
     */
    private function fetch_content($url, $selector) {
        $html = $this->fetch_url($url);
        if (!$html) return '';

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query($this->css_to_xpath($selector));

        if (!$nodes || $nodes->length === 0) return '';

        // 提取 HTML 内容
        $content = '';
        foreach ($nodes as $node) {
            $content .= $dom->saveHTML($node);
        }

        return $content;
    }

    /**
     * 简单的 CSS 选择器转 XPath（支持基本选择器）
     */
    private function css_to_xpath($css_selector) {
        if (empty($css_selector)) return '//*';

        // 处理 ID 选择器
        $css_selector = preg_replace('/#([a-zA-Z0-9_-]+)/', '[@id="$1"]', $css_selector);

        // 处理 class 选择器
        $css_selector = preg_replace('/\.([a-zA-Z0-9_-]+)/', '[contains(@class,"$1")]', $css_selector);

        // 处理属性选择器
        $css_selector = preg_replace('/\[([a-zA-Z0-9_-]+)\]/', '[@$1]', $css_selector);

        // 处理后代选择器
        $parts = explode(' ', trim($css_selector));
        $xpath = '//' . implode('//', $parts);

        return $xpath;
    }

    /**
     * 检查文章是否已存在（去重）
     */
    private function post_exists($title, $url = '') {
        global $wpdb;

        // 按标题去重
        $exists_by_title = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_status != 'trash' LIMIT 1",
            $title
        ));
        if ($exists_by_title) return true;

        // 按来源 URL 去重
        if (!empty($url)) {
            $exists_by_url = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_crawl_source_url' AND meta_value = %s LIMIT 1",
                $url
            ));
            if ($exists_by_url) return true;
        }

        return false;
    }

    /**
     * 将相对 URL 转换为绝对 URL
     */
    private function resolve_url($href, $base_url) {
        if (empty($href)) return '';
        if (strpos($href, 'http') === 0) return $href;

        $parsed = parse_url($base_url);
        $base   = $parsed['scheme'] . '://' . $parsed['host'];

        if (strpos($href, '/') === 0) {
            return $base . $href;
        }

        $path = dirname($parsed['path'] ?? '/');
        return $base . $path . '/' . $href;
    }

    /**
     * 解析各种格式的日期字符串
     */
    private function parse_date($date_str) {
        $date_str = trim($date_str);
        // 尝试常见日期格式
        $formats = ['Y-m-d', 'Y/m/d', 'Y年m月d日', 'm-d', 'Y-m-d H:i:s'];
        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $date_str);
            if ($dt) return $dt->format('Y-m-d H:i:s');
        }
        return current_time('mysql');
    }

    /**
     * 记录采集日志
     */
    private function log_crawl($task_id, $items_found, $items_saved, $error_msg = '') {
        global $wpdb;
        $wpdb->insert($this->table_logs, [
            'task_id'     => $task_id,
            'crawl_time'  => current_time('mysql'),
            'items_found' => $items_found,
            'items_saved' => $items_saved,
            'status'      => empty($error_msg) ? 'success' : 'error',
            'error_msg'   => $error_msg,
        ]);
    }

    /**
     * 定时任务：执行所有启用的采集任务
     */
    public function run_scheduled_crawl() {
        global $wpdb;
        $tasks = $wpdb->get_results(
            "SELECT * FROM {$this->table_tasks} WHERE is_active = 1"
        );

        foreach ($tasks as $task) {
            $this->execute_crawl_task($task);
        }
    }

    /**
     * AJAX：手动触发采集任务
     */
    public function ajax_run_crawl() {
        check_ajax_referer('cd_crawler_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('权限不足');
        }

        global $wpdb;
        $task_id = intval($_POST['task_id'] ?? 0);
        $task    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_tasks} WHERE id = %d", $task_id));

        if (!$task) {
            wp_send_json_error('任务不存在');
        }

        $result = $this->execute_crawl_task($task);
        wp_send_json_success($result);
    }
}

new CD_Data_Crawler();
```

### 8.3 推荐采集数据源

以下是成都本地资讯网站可以合规采集的主要官方数据源：

| 数据类型 | 来源网站 | 采集方式 | 更新频率 |
|---------|---------|---------|---------|
| 限行信息 | 成都交管局官网 | RSS / 页面采集 | 每日 |
| 招聘公告 | 成都人社局官网 | 页面采集 | 每日 |
| 公租房信息 | 成都住建局官网 | 页面采集 | 每周 |
| 活动公告 | 成都文旅局官网 | 页面采集 | 每日 |
| 医疗政策 | 成都卫健委官网 | 页面采集 | 每周 |
| 教育资讯 | 成都教育局官网 | 页面采集 | 每日 |
| 天气信息 | 和风天气 API | API 调用 | 实时 |
| 油价信息 | 国家发改委官网 | 页面采集 | 每月 |

---

## 9. SEO 与性能优化

### 9.1 SEO 优化策略

本地资讯门户的 SEO 优化应重点关注**本地化关键词**和**内容时效性**。建议采用以下策略：

**关键词策略**方面，页面标题应包含"成都"等地域词，如"2026成都限行尾号查询"、"成都五险一金计算器"等。同时为每个工具页面和资讯页面编写独特的 Meta Description，突出本地化和实用性特点。

**结构化数据**方面，为文章添加 Article 结构化数据，为工具页面添加 WebApplication 结构化数据，有助于在搜索结果中获得富媒体展示效果。

```php
<?php
// 为工具页面添加结构化数据
function cd_add_structured_data() {
    if (is_page('traffic-limit')) {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebApplication',
            'name'        => '成都限行查询工具',
            'description' => '查询成都今日及近期机动车尾号限行信息，支持车牌号查询',
            'url'         => get_permalink(),
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem'     => 'Web Browser',
        ];
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
add_action('wp_head', 'cd_add_structured_data');
```

### 9.2 性能优化方案

| 优化层面 | 具体措施 | 预期效果 |
|---------|---------|---------|
| 页面缓存 | WP Super Cache + Redis 对象缓存 | 减少数据库查询 80% |
| 图片优化 | WebP 格式 + 懒加载 + CDN 分发 | 减少图片流量 60% |
| 代码压缩 | CSS/JS 合并压缩（Autoptimize 插件） | 减少 HTTP 请求 50% |
| 数据库优化 | 定期清理修订版本、垃圾评论 | 减少数据库体积 |
| CDN 加速 | 静态资源上传至阿里云 OSS + CDN | 全国访问速度提升 |
| 数据库索引 | 为常用查询字段添加索引 | 查询速度提升 10 倍 |

---

## 10. 安全与合规

### 10.1 WordPress 安全加固

```php
<?php
// 在 wp-config.php 中添加安全配置

// 禁止 XML-RPC（如不需要可禁用）
add_filter('xmlrpc_enabled', '__return_false');

// 隐藏 WordPress 版本号
remove_action('wp_head', 'wp_generator');

// 禁止用户名枚举
if (!is_admin()) {
    if (isset($_REQUEST['author'])) {
        wp_redirect(home_url(), 301);
        exit;
    }
}

// 限制登录尝试次数（配合 Wordfence 插件）
// 建议将 wp-login.php 路径修改为自定义路径
```

### 10.2 数据采集合规要求

在开展数据采集时，必须严格遵守以下合规原则：

**技术合规**方面，必须检查并遵守目标网站的 `robots.txt` 文件规定，合理控制采集频率（建议每次请求间隔不少于 1 秒），并在 User-Agent 中标明爬虫身份和联系方式。

**法律合规**方面，采集的内容必须标注原始来源，不得删除或修改版权声明。对于政府官方信息，可以合理引用但需注明来源。不得采集涉及个人隐私的信息，不得将采集内容用于商业转售。

**内容合规**方面，发布前需对采集内容进行人工审核（建议将采集状态默认设为"草稿"），确保内容的准确性和合法性。对于政策类信息，应标注发布日期和有效期。

---

## 11. 运维与监控

### 11.1 定时任务配置

```bash
# 在服务器 crontab 中添加 WordPress Cron 触发器
# 每5分钟触发一次 WordPress 定时任务
*/5 * * * * wget -q -O /dev/null https://your-domain.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1

# 每天凌晨2点执行数据库备份
0 2 * * * /usr/local/mysql/bin/mysqldump -u root -pYOURPASSWORD chengdu_news | gzip > /backup/db_$(date +\%Y\%m\%d).sql.gz

# 每周清理30天前的采集日志
0 3 * * 0 mysql -u root -pYOURPASSWORD chengdu_news -e "DELETE FROM cd_crawl_logs WHERE crawl_time < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

### 11.2 监控告警配置

建议集成以下监控工具，确保网站稳定运行：

**网站可用性监控**：使用 UptimeRobot（免费版支持 50 个监控点）或阿里云云监控，设置网站宕机时立即短信告警。

**性能监控**：定期使用 Google PageSpeed Insights 或 GTmetrix 检测页面加载速度，目标是首屏加载时间控制在 3 秒以内。

**错误日志监控**：配置 Nginx 和 PHP 错误日志，并使用 ELK Stack 或简单的日志分析脚本定期检查异常。

---

## 12. 参考资料

[1] 成都本地宝官网 - 功能模块参考 https://cd.bendibao.com/

[2] WordPress 官方开发文档 - 插件开发指南 https://developer.wordpress.org/plugins/

[3] WordPress 官方文档 - 主题开发 https://developer.wordpress.org/themes/

[4] 成都市2025年社保缴费标准 - 成都本地宝 http://cd.bendibao.com/live/202355/160581.shtm

[5] 成都住房公积金缴存基数及比例 - 成都公积金管理中心 https://m12333.cn/policy/pbfui.html

[6] 车辆限行查询 API - 聚合数据 https://www.juhe.cn/wiki/clxxcxapijk633

[7] 心知天气 API 文档 - 机动车尾号限行 https://docs.seniverse.com/api/life/driving.html

[8] WordPress 采集插件开发教程 - 好主机测评 https://www.hzjcp.com/2212.html

[9] 胖鼠采集插件 - WordPress.org https://wordpress.org/plugins/fat-rat-collect/

[10] 成都限行规定（时间+区域+规则）- 成都本地宝 http://cd.bendibao.com/traffic/201988/104076.shtm

---

*本指南由 Manus AI 生成，内容仅供参考。实际开发中请根据最新的 WordPress 版本、成都市政策规定及相关法律法规进行调整。*
