# 化肥行业实时数据平台 - 网站源码

## 项目简介

本仓库保存了 [化肥行业实时数据平台](http://1112.chaxunwa.com) 的主题和插件源码，用于版本管理和备份。

## 目录结构

```
├── theme/                          # WordPress 主题 (Fertilizer Price Platform)
│   ├── index.php                   # 首页模板（含价格趋势图表）
│   ├── functions.php               # 主题功能配置
│   ├── style.css                   # 主题样式
│   ├── header.php                  # 页头模板
│   ├── footer.php                  # 页脚模板
│   ├── single.php                  # 单篇文章模板
│   ├── archive.php                 # 归档页模板
│   ├── search.php                  # 搜索页模板
│   └── js/
│       └── main.js                 # 主题 JavaScript（价格卡片、图表等）
│
└── plugin/                         # WordPress 插件 (Fertilizer Platform)
    ├── fertilizer-platform.php     # 插件主文件（REST API、数据管理）
    └── assets/
        └── fertp.js                # 插件图表组件（移动端优化版）
```

## 最近更新记录

### 2026-03-07 移动端图表优化
- **index.php**：将 X 轴日期格式从 `2026-02-05 09:00:00` 改为简洁的 `02-05` 格式，防止标签重叠
- **index.php**：图表配置添加移动端响应式支持（图例位置、字体大小、标签数量自适应）
- **index.php**：图表容器高度设置为 380px，提升可视化效果
- **main.js**：添加 `formatDateLabel` 函数和 `isMobile` 检测
- **fertp.js**：添加移动端优化（数据点大小、图例位置、X 轴标签旋转角度）

## 部署说明

- **服务器**：宝塔面板 + WordPress
- **网站地址**：http://1112.chaxunwa.com
- **主题路径**：`/wp-content/themes/fertilizer-theme/`
- **插件路径**：`/wp-content/plugins/fertilizer-platform/`
