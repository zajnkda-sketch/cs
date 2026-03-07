# 化肥行业实时数据平台 - 网站源码

## 项目简介

本仓库保存了 [化肥行业实时数据平台](http://1112.chaxunwa.com) 的主题和插件源码，用于版本管理和备份。

## 目录结构

```
├── theme/                          # WordPress 主题 (Fertilizer Price Platform)
│   ├── index.php                   # 首页模板（含价格趋势图表）
│   ├── functions.php               # 主题功能配置（脚本注册、AJAX nonce 注入）
│   ├── style.css                   # 主题样式
│   ├── header.php                  # 页头模板
│   ├── footer.php                  # 页脚模板
│   ├── single.php                  # 单篇文章模板
│   ├── archive.php                 # 归档页模板
│   ├── search.php                  # 搜索页模板
│   └── js/
│       └── main.js                 # 主题 JavaScript（价格卡片、图表等）
│
├── plugin/                         # WordPress 插件 (Fertilizer Platform)
│   ├── fertilizer-platform.php     # 插件主文件（REST API、数据管理、后台页面）
│   └── assets/
│       └── fertp.js                # 插件图表组件（移动端优化版）
│
└── auto_price_update.php           # 宝塔定时任务脚本（每日自动写入价格）
```

## 最近更新记录

### 2026-03-07 修复后台历史数据不显示 & 前端折线图不更新

**根本原因：** `plugin/fertilizer-platform.php` 文件为空，导致：
- 没有 REST API 端点，`fertp.js` 无法获取价格序列数据
- 没有 AJAX 处理函数，`main.js` 的图表请求无响应
- 没有后台管理页面，历史数据无法在 WordPress 后台查看

**本次修复内容：**

| 文件 | 修改说明 |
|------|---------|
| `plugin/fertilizer-platform.php` | **完整重建**：添加建表逻辑、REST API（`/wp-json/fert/v1/prices`、`/wp-json/fert/v1/items`）、AJAX 处理函数、后台管理页面（概览/历史数据/手动录入） |
| `theme/functions.php` | **完整重建**：注册 Bootstrap、Chart.js、主题 CSS/JS，向 `main.js` 注入 `fertilizer_ajax`（AJAX URL + nonce） |
| `auto_price_update.php` | **修复今日检查逻辑**：原版按全部品种整体检查（任一品种有数据即跳过全部），改为按品种逐一检查，避免部分品种缺数据时被整体跳过；新增 `ensure_tables()` 函数，兼容插件未激活时直接运行脚本的场景 |

### 2026-03-07 移动端图表优化
- **index.php**：X 轴日期格式从 `2026-02-05 09:00:00` 改为简洁的 `02-05` 格式
- **main.js**：添加 `formatDateLabel` 函数和 `isMobile` 检测
- **fertp.js**：添加移动端优化（数据点大小、图例位置、X 轴标签旋转角度）

## 部署说明

- **服务器**：宝塔面板 + WordPress
- **网站地址**：http://1112.chaxunwa.com
- **主题路径**：`/wp-content/themes/fertilizer-theme/`
- **插件路径**：`/wp-content/plugins/fertilizer-platform/`

## 部署步骤（修复后）

1. 将 `plugin/fertilizer-platform.php` 上传到 `/wp-content/plugins/fertilizer-platform/fertilizer-platform.php`
2. 将 `theme/functions.php` 上传到 `/wp-content/themes/fertilizer-theme/functions.php`
3. 将 `auto_price_update.php` 上传到网站根目录 `/www/wwwroot/1112.chaxunwa.com/`
4. 在 WordPress 后台 → 插件 → 停用后重新**激活** Fertilizer Platform 插件（触发建表逻辑）
5. 在宝塔终端执行一次 `php /www/wwwroot/1112.chaxunwa.com/auto_price_update.php` 验证数据写入
6. 刷新前端页面，折线图应正常显示价格趋势
