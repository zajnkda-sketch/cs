<?php
/**
 * 数据采集引擎核心类
 * @package CD_Data_Crawler
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Crawler_Engine {

    private $task;
    private $new_count = 0;
    private $dup_count = 0;
    private $errors    = [];

    public function __construct( $task ) {
        $this->task = $task;
    }

    /* ---- 执行采集任务 ---- */
    public function run() {
        $url = $this->task->source_url;

        // 检查 robots.txt 合规
        if ( ! $this->check_robots( $url ) ) {
            CD_Crawler_DB::insert_log( $this->task->id, 'blocked', 'robots.txt 禁止采集：' . $url );
            return false;
        }

        $html = $this->fetch( $url );
        if ( ! $html ) {
            CD_Crawler_DB::insert_log( $this->task->id, 'error', '无法获取页面内容：' . $url );
            return false;
        }

        $items = $this->parse_list( $html, $url );
        if ( empty( $items ) ) {
            CD_Crawler_DB::insert_log( $this->task->id, 'warning', '未解析到任何条目，请检查 CSS 选择器', 0, 0 );
            return false;
        }

        foreach ( $items as $item ) {
            $this->process_item( $item );
        }

        // 更新任务状态
        CD_Crawler_DB::update_task( $this->task->id, [
            'last_run'  => current_time( 'mysql' ),
            'next_run'  => date( 'Y-m-d H:i:s', time() + (int) $this->task->interval ),
            'run_count' => (int) $this->task->run_count + 1,
        ] );

        $msg = sprintf( '采集完成：新增 %d 篇，重复 %d 篇', $this->new_count, $this->dup_count );
        if ( $this->errors ) $msg .= '；错误：' . implode( '；', $this->errors );

        CD_Crawler_DB::insert_log(
            $this->task->id,
            $this->errors ? 'warning' : 'success',
            $msg,
            $this->new_count,
            $this->dup_count
        );

        return true;
    }

    /* ---- HTTP 请求（带重试）---- */
    private function fetch( $url, $retry = 2 ) {
        for ( $i = 0; $i <= $retry; $i++ ) {
            $resp = wp_remote_get( $url, [
                'timeout'    => 20,
                'user-agent' => 'Mozilla/5.0 (compatible; ChengduLifeBot/1.0; +https://chengdulife.com/bot)',
                'headers'    => [ 'Accept-Language' => 'zh-CN,zh;q=0.9' ],
                'sslverify'  => false,
            ] );

            if ( is_wp_error( $resp ) ) {
                if ( $i === $retry ) {
                    $this->errors[] = $resp->get_error_message();
                    return false;
                }
                sleep( 2 );
                continue;
            }

            $code = wp_remote_retrieve_response_code( $resp );
            if ( $code !== 200 ) {
                $this->errors[] = "HTTP {$code}：{$url}";
                return false;
            }

            $body = wp_remote_retrieve_body( $resp );

            // 编码检测与转换
            if ( preg_match( '/charset=["\']?(gb2312|gbk|gb18030)/i', $body ) ) {
                $body = mb_convert_encoding( $body, 'UTF-8', 'GBK' );
            }

            return $body;
        }
        return false;
    }

    /* ---- 解析列表页 ---- */
    private function parse_list( $html, $base_url ) {
        libxml_use_internal_errors( true );
        $doc = new DOMDocument();
        $doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();

        $xpath = new DOMXPath( $doc );
        $items = [];

        // 将 CSS 选择器转为 XPath（简化实现，支持常见选择器）
        $list_xpath = $this->css_to_xpath( $this->task->list_sel );
        $nodes      = $xpath->query( $list_xpath );

        if ( ! $nodes || $nodes->length === 0 ) return [];

        foreach ( $nodes as $node ) {
            $item = [];

            // 标题
            if ( $this->task->title_sel ) {
                $t = $this->find_in_node( $xpath, $node, $this->task->title_sel );
                $item['title'] = trim( $t );
            }

            // 链接
            if ( $this->task->link_sel ) {
                $l = $this->find_attr_in_node( $xpath, $node, $this->task->link_sel, 'href' );
                $item['link'] = $this->resolve_url( $l, $base_url );
            } elseif ( $node->nodeName === 'a' ) {
                $item['link'] = $this->resolve_url( $node->getAttribute('href'), $base_url );
            }

            // 日期
            if ( $this->task->date_sel ) {
                $item['date'] = trim( $this->find_in_node( $xpath, $node, $this->task->date_sel ) );
            }

            // 图片
            if ( $this->task->img_sel ) {
                $img = $this->find_attr_in_node( $xpath, $node, $this->task->img_sel, 'src' );
                $item['image'] = $this->resolve_url( $img, $base_url );
            }

            if ( ! empty( $item['title'] ) && ! empty( $item['link'] ) ) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /* ---- 处理单条条目 ---- */
    private function process_item( $item ) {
        $hash = md5( $item['link'] ?? $item['title'] );

        // 去重检查
        if ( CD_Crawler_DB::hash_exists( $this->task->id, $hash ) ) {
            $this->dup_count++;
            return;
        }

        // 采集详情页内容（如配置了 content_sel）
        $content = '';
        if ( $this->task->content_sel && ! empty( $item['link'] ) ) {
            $detail_html = $this->fetch( $item['link'] );
            if ( $detail_html ) {
                $content = $this->parse_content( $detail_html );
            }
            // 礼貌延迟，避免频繁请求
            usleep( 800000 ); // 0.8秒
        }

        // 创建 WordPress 文章
        $post_data = [
            'post_title'   => wp_strip_all_tags( $item['title'] ),
            'post_content' => $content ?: '',
            'post_excerpt' => mb_substr( wp_strip_all_tags( $content ), 0, 200 ),
            'post_status'  => 'draft', // 默认草稿，人工审核后发布
            'post_author'  => 1,
            'post_date'    => ! empty( $item['date'] ) ? $this->parse_date( $item['date'] ) : current_time( 'mysql' ),
        ];

        if ( $this->task->category_id ) {
            $post_data['post_category'] = [ (int) $this->task->category_id ];
        }

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            $this->errors[] = '创建文章失败：' . $post_id->get_error_message();
            return;
        }

        // 保存来源 URL
        update_post_meta( $post_id, '_cdcr_source_url',  $item['link'] ?? '' );
        update_post_meta( $post_id, '_cdcr_task_id',     $this->task->id );
        update_post_meta( $post_id, '_cdcr_crawled_at',  current_time( 'mysql' ) );

        // 下载特色图片
        if ( ! empty( $item['image'] ) ) {
            $this->set_featured_image( $post_id, $item['image'], $item['title'] );
        }

        // 记录哈希
        CD_Crawler_DB::insert_hash( $this->task->id, $hash, $item['link'] ?? '' );
        $this->new_count++;
    }

    /* ---- 解析详情页正文 ---- */
    private function parse_content( $html ) {
        if ( ! $this->task->content_sel ) return '';

        libxml_use_internal_errors( true );
        $doc = new DOMDocument();
        $doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();

        $xpath   = new DOMXPath( $doc );
        $x_path  = $this->css_to_xpath( $this->task->content_sel );
        $nodes   = $xpath->query( $x_path );

        if ( ! $nodes || $nodes->length === 0 ) return '';

        $node = $nodes->item(0);
        return $doc->saveHTML( $node );
    }

    /* ---- 设置特色图片 ---- */
    private function set_featured_image( $post_id, $img_url, $title ) {
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attach_id = media_sideload_image( $img_url, $post_id, $title, 'id' );
        if ( ! is_wp_error( $attach_id ) ) {
            set_post_thumbnail( $post_id, $attach_id );
        }
    }

    /* ---- CSS 选择器转 XPath（简化版）---- */
    private function css_to_xpath( $css ) {
        if ( empty( $css ) ) return '//*';

        // 支持：tag、.class、#id、tag.class、tag > child、tag span 等常见选择器
        $parts = preg_split( '/\s+/', trim( $css ) );
        $xpath = '//' . $this->convert_simple_selector( array_shift( $parts ) );

        foreach ( $parts as $part ) {
            if ( $part === '>' ) continue;
            $xpath .= '//' . $this->convert_simple_selector( $part );
        }

        return $xpath;
    }

    private function convert_simple_selector( $sel ) {
        // #id
        if ( preg_match( '/^#(.+)$/', $sel, $m ) ) {
            return '*[@id="' . $m[1] . '"]';
        }
        // .class
        if ( preg_match( '/^\.(.+)$/', $sel, $m ) ) {
            return '*[contains(@class,"' . $m[1] . '")]';
        }
        // tag.class
        if ( preg_match( '/^([a-z0-9]+)\.(.+)$/i', $sel, $m ) ) {
            return $m[1] . '[contains(@class,"' . $m[2] . '")]';
        }
        // tag#id
        if ( preg_match( '/^([a-z0-9]+)#(.+)$/i', $sel, $m ) ) {
            return $m[1] . '[@id="' . $m[2] . '"]';
        }
        // plain tag
        return $sel ?: '*';
    }

    private function find_in_node( $xpath, $node, $sel ) {
        $x     = './/' . $this->convert_simple_selector( $sel );
        $nodes = $xpath->query( $x, $node );
        return $nodes && $nodes->length > 0 ? $nodes->item(0)->textContent : '';
    }

    private function find_attr_in_node( $xpath, $node, $sel, $attr ) {
        $x     = './/' . $this->convert_simple_selector( $sel );
        $nodes = $xpath->query( $x, $node );
        return $nodes && $nodes->length > 0 ? $nodes->item(0)->getAttribute( $attr ) : '';
    }

    /* ---- URL 解析 ---- */
    private function resolve_url( $url, $base ) {
        if ( empty( $url ) ) return '';
        if ( preg_match( '/^https?:\/\//i', $url ) ) return $url;
        $parsed = parse_url( $base );
        $scheme = $parsed['scheme'] ?? 'https';
        $host   = $parsed['host']   ?? '';
        if ( strpos( $url, '//' ) === 0 ) return $scheme . ':' . $url;
        if ( strpos( $url, '/' ) === 0 )  return $scheme . '://' . $host . $url;
        $path = rtrim( dirname( $parsed['path'] ?? '/' ), '/' );
        return $scheme . '://' . $host . $path . '/' . $url;
    }

    /* ---- 日期解析 ---- */
    private function parse_date( $str ) {
        $str = trim( $str );
        // 尝试常见格式
        $formats = [ 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'Y/m/d', 'Y年m月d日', 'm-d', 'm月d日' ];
        foreach ( $formats as $fmt ) {
            $d = DateTime::createFromFormat( $fmt, $str );
            if ( $d ) return $d->format( 'Y-m-d H:i:s' );
        }
        return current_time( 'mysql' );
    }

    /* ---- robots.txt 检查 ---- */
    private function check_robots( $url ) {
        $parsed   = parse_url( $url );
        $robots_url = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' ) . '/robots.txt';
        $resp     = wp_remote_get( $robots_url, [ 'timeout' => 5, 'sslverify' => false ] );
        if ( is_wp_error( $resp ) ) return true; // 无法获取则默认允许

        $body     = wp_remote_retrieve_body( $resp );
        $path     = $parsed['path'] ?? '/';
        $ua_match = false;

        foreach ( explode( "\n", $body ) as $line ) {
            $line = trim( $line );
            if ( stripos( $line, 'User-agent: *' ) === 0 || stripos( $line, 'User-agent: ChengduLifeBot' ) === 0 ) {
                $ua_match = true;
            }
            if ( $ua_match && stripos( $line, 'Disallow:' ) === 0 ) {
                $disallow = trim( substr( $line, 9 ) );
                if ( $disallow && strpos( $path, $disallow ) === 0 ) {
                    return false; // 被禁止
                }
            }
            if ( $ua_match && stripos( $line, 'User-agent:' ) === 0 && ! stripos( $line, '*' ) ) {
                $ua_match = false;
            }
        }
        return true;
    }
}
