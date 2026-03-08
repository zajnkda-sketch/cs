# 快速开始指南

本文档帮助开发者在最短时间内搭建起成都资讯门户网站的基础环境。

## 第一步：准备服务器

推荐使用阿里云或腾讯云的成都节点服务器，配置建议：**2核4GB内存，100GB SSD，5Mbps带宽**。操作系统选择 Ubuntu 22.04 LTS。

## 第二步：安装 LNMP 环境

```bash
# 连接服务器后执行
wget http://soft.vpser.net/lnmp/lnmp2.0.tar.gz
tar -zxvf lnmp2.0.tar.gz && cd lnmp2.0
./install.sh lnmp
# 安装过程中选择：MySQL 8.0 + PHP 8.2
```

## 第三步：安装 WordPress

```bash
# 创建数据库
mysql -u root -p -e "CREATE DATABASE chengdu_news CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'wp_user'@'localhost' IDENTIFIED BY 'your_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON chengdu_news.* TO 'wp_user'@'localhost';"

# 下载并配置 WordPress
cd /home/wwwroot/your-domain.com
wget https://cn.wordpress.org/latest-zh_CN.zip
unzip latest-zh_CN.zip --strip-components=1
cp wp-config-sample.php wp-config.php
# 编辑 wp-config.php，填入数据库信息
```

## 第四步：安装基础插件

在 WordPress 后台 → 插件 → 安装插件，搜索并安装以下插件：

| 插件 | 用途 |
|------|------|
| Yoast SEO | SEO 优化 |
| WP Super Cache | 页面缓存 |
| Advanced Custom Fields | 自定义字段 |
| Wordfence Security | 安全防护 |
| WP-Optimize | 数据库优化 |

## 第五步：上传自定义插件

将本仓库 `plugins/` 目录下的插件文件夹上传至服务器的 `wp-content/plugins/` 目录，然后在后台激活：

- **CD Traffic Limit** → 提供限行查询功能
- **CD Social Calculator** → 提供五险一金计算功能
- **CD Data Crawler** → 提供数据自动采集功能

## 第六步：创建核心页面

在 WordPress 后台 → 页面 → 新建页面，创建以下页面并嵌入对应短代码：

| 页面名称 | URL Slug | 嵌入短代码 |
|---------|---------|-----------|
| 成都限行查询 | `/traffic-limit` | `[cd_traffic_limit]` |
| 五险一金计算器 | `/social-calculator` | `[cd_social_calculator]` |
| 办事指南 | `/guide` | 使用办事指南页面模板 |

## 第七步：配置数据采集任务

进入 WordPress 后台 → **数据采集** 菜单，添加以下采集任务：

1. **成都交管局限行公告** - 采集成都交管局官网最新限行通知
2. **成都人社局招聘公告** - 采集政府招聘信息
3. **成都住建局公租房信息** - 采集住房保障信息

## 常见问题

**Q：采集任务不执行怎么办？**
A：WordPress 的定时任务依赖页面访问触发，建议在服务器 crontab 中添加定时触发命令：
```bash
*/5 * * * * wget -q -O /dev/null "https://your-domain.com/wp-cron.php?doing_wp_cron"
```

**Q：限行数据不准确怎么办？**
A：成都限行规则每年可能调整，请定期对照成都交管局官网更新插件中的限行轮换规则。也可以在插件后台手动录入最新的限行数据。

**Q：如何提高网站访问速度？**
A：依次执行以下操作：①激活 WP Super Cache 插件并开启缓存；②安装 Redis 并激活 Redis Object Cache 插件；③将静态资源（图片、CSS、JS）上传至 CDN；④在 Nginx 配置中开启 Gzip 压缩。

**Q：采集内容是否会有版权问题？**
A：建议将采集状态设置为"草稿"，经人工审核后再发布。采集政府官方信息时，需在文章中标注"来源：XXX官网"，并提供原文链接。商业化运营前建议咨询法律顾问。
