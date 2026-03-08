# 项目目录结构说明

本文档描述成都资讯门户 WordPress 网站的完整目录结构，供开发团队参考。

## 仓库结构

```
chengdu-wordpress-guide/
├── README.md                          # 主开发指南（核心文档）
├── STRUCTURE.md                       # 本文件：目录结构说明
├── plugins/                           # 自定义插件目录
│   ├── cd-traffic-limit/              # 成都限行查询插件
│   │   ├── cd-traffic-limit.php       # 插件主文件
│   │   ├── includes/
│   │   │   ├── class-traffic-limit.php  # 核心类
│   │   │   └── class-admin.php          # 后台管理类
│   │   └── assets/
│   │       ├── css/
│   │       │   └── traffic-limit.css    # 前端样式
│   │       └── js/
│   │           └── traffic-limit.js     # 前端脚本（已提供）
│   ├── cd-social-calculator/          # 五险一金计算插件
│   │   ├── cd-social-calculator.php   # 插件主文件
│   │   ├── includes/
│   │   │   └── class-calculator.php   # 计算核心类
│   │   └── assets/
│   │       ├── css/
│   │       │   └── social-calc.css    # 前端样式
│   │       └── js/
│   │           └── social-calc.js     # 前端脚本（已提供）
│   ├── cd-data-crawler/               # 数据采集插件
│   │   ├── cd-data-crawler.php        # 插件主文件（已提供核心代码）
│   │   ├── includes/
│   │   │   ├── class-crawler.php      # 采集核心类
│   │   │   └── class-admin.php        # 后台管理类
│   │   └── assets/
│   │       ├── css/
│   │       │   └── crawler-admin.css  # 后台样式
│   │       └── js/
│   │           └── crawler-admin.js   # 后台脚本
│   ├── cd-local-tools/                # 其他实用工具插件
│   │   ├── cd-local-tools.php         # 插件主文件
│   │   └── tools/
│   │       ├── income-tax.php         # 个税计算工具
│   │       ├── gjj-calculator.php     # 公积金计算工具
│   │       └── weather.php            # 天气查询工具
│   └── cd-news-aggregator/            # 新闻聚合插件
│       ├── cd-news-aggregator.php     # 插件主文件
│       └── includes/
│           └── class-rss-reader.php   # RSS 读取类
└── theme/                             # 自定义主题目录
    ├── chengdu-news/                  # 主题根目录
    │   ├── style.css                  # 主题样式表（含主题信息）
    │   ├── functions.php              # 主题功能函数
    │   ├── index.php                  # 主模板
    │   ├── header.php                 # 页头模板
    │   ├── footer.php                 # 页脚模板
    │   ├── sidebar.php                # 侧边栏模板
    │   ├── front-page.php             # 首页模板
    │   ├── single.php                 # 文章详情模板
    │   ├── archive.php                # 归档页模板
    │   ├── search.php                 # 搜索结果模板
    │   ├── 404.php                    # 404 错误页
    │   ├── page-templates/
    │   │   ├── page-tools.php         # 工具页面模板
    │   │   └── page-guide.php         # 办事指南模板
    │   ├── template-parts/
    │   │   ├── content-news.php       # 新闻内容片段
    │   │   └── widget-tools.php       # 工具小组件
    │   ├── assets/
    │   │   ├── css/
    │   │   │   ├── main.css           # 主样式
    │   │   │   └── responsive.css     # 响应式样式
    │   │   └── js/
    │   │       └── main.js            # 主脚本
    │   └── inc/
    │       ├── customizer.php         # 主题定制器
    │       └── helpers.php            # 辅助函数
```

## 部署步骤

1. 将 `plugins/` 目录下的各插件文件夹上传至 WordPress 的 `wp-content/plugins/` 目录
2. 将 `theme/chengdu-news/` 目录上传至 WordPress 的 `wp-content/themes/` 目录
3. 在 WordPress 后台激活主题和各插件
4. 按照 `README.md` 中的配置说明完成初始化设置
5. 在"数据采集"后台页面配置采集任务
6. 使用短代码 `[cd_traffic_limit]` 和 `[cd_social_calculator]` 在页面中嵌入查询工具
