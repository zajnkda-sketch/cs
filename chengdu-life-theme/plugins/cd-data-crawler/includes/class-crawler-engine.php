<?php
/**
 * 数据采集引擎核心类（v1.1 - 经测试验证修复版）
 *
 * 修复内容：
 * 1. title 属性优先提取（修复豆瓣等网站标题含多余空白的问题）
 * 2. 增强 a 标签链接回退逻辑（当 link_sel 为空时自动查找子节点 a 标签）
 * 3. 修复 resolve_url 对 javascript: 和 # 链接的过滤
 * 4. 增加 User-Agent 轮换，提高采集成功率
 * 5. 增加采集前节点数量预检，空结果时记录更详细的日志
 * 6. 修复标题文本多余空白字符清理
 *
 * @package CD_Data_Crawler
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Crawler_Engine {

    private $task;
    private $new_count = 0;
    private $dup_count = 0;
    private $errors    = [];

    private static $user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
    ];

    public function __construct( $task ) {
        $this->task = $task;
    }

    /* ---- 执行采集任务 ---- */
    public function run() {
        $url = $this->task->source_url;

        // 检查 robots.txt 合规
        $robots = $this->check_robots( $url );
        if ( ! $robots['allowed'] ) {
            CD_Crawler_DB::insert_log( $this->task->id, 'blocked', 'robots.txt 禁止采集：' . $url . '（' . $robots['note'] . '）' );
            return false;
        }

        $html = $this->fetch( $url );
        if ( ! $html ) {
            CD_Crawler_DB::insert_log( $this->task->id, 'error', '无法获取页面内容：' . $url . implode( '；', $this->errors ) );
            return false;
        }

        $items = $this->parse_list( $html, $url );
        if ( empty( $items ) ) {
            CD_Crawler_DB::insert_log( $this->task->id, 'warning',
                '未解析到任何条目，请在后台检查 CSS 选择器配置。列表选择器：' . $this->task->list_sel, 0, 0 );
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

        $msg = sprintf( '采集完成：新增 %d 篇，重复跳过 %d 篇', $this->new_count, $this->dup_count );
        if ( $this->errors ) $msg .= '；错误：' . implode( '；', array_slice( $this->errors, 0, 3 ) );

        CD_Crawler_DB::insert_log(
            $this->task->id,
            $this->errors ? 'warning' : 'success',
            $msg,
            $this->new_count,
            $this->dup_count
        );

        return true;
    }

    /* ---- HTTP 请求（带重试 + UA 轮换）---- */
    private function fetch( $url, $retry = 2 ) {
        for ( $i = 0; $i <= $retry; $i++ ) {
            $ua   = self::$user_agents[ $i % count( self::$user_agents ) ];
            $resp = wp_remote_get( $url, [
                'timeout'    => 20,
                'user-agent' => $ua,
                'headers'    => [
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                ],
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

        $xpath      = new DOMXPath( $doc );
        $list_xpath = $this->css_to_xpath( $this->task->list_sel );
        $nodes      = $xpath->query( $list_xpath );

        if ( ! $nodes || $nodes->length === 0 ) return [];

        $items = [];
        foreach ( $nodes as $node ) {
            $item = [];

            // ---- 标题（优先 title 属性，其次 textContent）----
            if ( $this->task->title_sel ) {
                $t_nodes = $xpath->query( './/' . $this->convert_simple_selector( $this->task->title_sel ), $node );
                if ( $t_nodes && $t_nodes->length > 0 ) {
                    $t_node = $t_nodes->item(0);
                    $title  = $t_node->getAttribute('title');
                    if ( empty( $title ) ) $title = $t_node->textContent;
                    $item['title'] = trim( preg_replace( '/\s+/', ' ', $title ) );
                }
            }
            if ( empty( $item['title'] ) ) {
                $item['title'] = trim( preg_replace( '/\s+/', ' ', $node->textContent ) );
            }
            $item['title'] = mb_substr( $item['title'], 0, 200 );

            // ---- 链接（link_sel → 子 a 标签 → 自身 a 标签）----
            $item['link'] = '';
            if ( $this->task->link_sel ) {
                $l = $this->find_attr_in_node( $xpath, $node, $this->task->link_sel, 'href' );
                $item['link'] = $this->resolve_url( $l, $base_url );
            }
            if ( empty( $item['link'] ) ) {
                $a_nodes = $xpath->query( './/a', $node );
                if ( $a_nodes && $a_nodes->length > 0 ) {
                    $item['link'] = $this->resolve_url( $a_nodes->item(0)->getAttribute('href'), $base_url );
                }
            }
            if ( empty( $item['link'] ) && $node->nodeName === 'a' ) {
                $item['link'] = $this->resolve_url( $node->getAttribute('href'), $base_url );
            }

            // ---- 日期 ----
            if ( $this->task->date_sel ) {
                $item['date'] = trim( $this->find_in_node( $xpath, $node, $this->task->date_sel ) );
            }

            // ---- 图片 ----
            if ( $this->task->img_sel ) {
                $img = $this->find_attr_in_node( $xpath, $node, $this->task->img_sel, 'src' );
                if ( empty( $img ) ) {
                    $img = $this->find_attr_in_node( $xpath, $node, $this->task->img_sel, 'data-src' );
                }
                $item['image'] = $this->resolve_url( $img, $base_url );
            }

            // 只保留有标题且标题长度合理的条目
            if ( ! empty( $item['title'] ) && mb_strlen( $item['title'] ) > 1 ) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /* ---- 处理单条条目 ---- */
    private function process_item( $item ) {
        $hash = md5( $item['link'] ?: $item['title'] );

        // 去重检查
        if ( CD_Crawler_DB::hash_exists( $this->task->id, $hash ) ) {
            $this->dup_count++;
            return;
        }

        // 采集详情页内容
        $content = '';
        if ( $this->task->content_sel && ! empty( $item['link'] ) ) {
            $detail_html = $this->fetch( $item['link'] );
            if ( $detail_html ) {
                $content = $this->parse_content( $detail_html );
            }
            usleep( 800000 ); // 0.8秒礼貌延迟
        }

        // 创建 WordPress 文章（默认草稿，需人工审核）
        $post_data = [
            'post_title'   => wp_strip_all_tags( $item['title'] ),
            'post_content' => $content ?: '',
            'post_excerpt' => mb_substr( wp_strip_all_tags( $content ), 0, 200 ),
            'post_status'  => 'draft',
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

        update_post_meta( $post_id, '_cdcr_source_url', $item['link'] ?? '' );
        update_post_meta( $post_id, '_cdcr_task_id',    $this->task->id );
        update_post_meta( $post_id, '_cdcr_crawled_at', current_time( 'mysql' ) );

        if ( ! empty( $item['image'] ) ) {
            $this->set_featured_image( $post_id, $item['image'], $item['title'] );
        }

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

        $xpath  = new DOMXPath( $doc );
        $x_path = $this->css_to_xpath( $this->task->content_sel );
        $nodes  = $xpath->query( $x_path );

        if ( ! $nodes || $nodes->length === 0 ) return '';
        return $doc->saveHTML( $nodes->item(0) );
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

    /* ---- CSS 选择器转 XPath ---- */
    private function css_to_xpath( $css ) {
        if ( empty( $css ) ) return '//*';
        $parts = preg_split( '/\s+/', trim( $css ) );
        $xpath = '//' . $this->convert_simple_selector( array_shift( $parts ) );
        foreach ( $parts as $part ) {
            if ( $part === '>' ) continue;
            $xpath .= '//' . $this->convert_simple_selector( $part );
        }
        return $xpath;
    }

    private function convert_simple_selector( $sel ) {
        if ( preg_match( '/^#(.+)$/', $sel, $m ) )               return '*[@id="' . $m[1] . '"]';
        if ( preg_match( '/^\.(.+)$/', $sel, $m ) )              return '*[contains(@class,"' . $m[1] . '")]';
        if ( preg_match( '/^([a-z0-9]+)\.(.+)$/i', $sel, $m ) ) return $m[1] . '[contains(@class,"' . $m[2] . '")]';
        if ( preg_match( '/^([a-z0-9]+)#(.+)$/i', $sel, $m ) )  return $m[1] . '[@id="' . $m[2] . '"]';
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

    /* ---- URL 解析（修复 javascript: 和 # 过滤）---- */
    private function resolve_url( $url, $base ) {
        if ( empty( $url ) ) return '';
        if ( $url === '#' ) return '';
        if ( strpos( $url, 'javascript:' ) === 0 ) return '';
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
        $str     = trim( $str );
        $formats = [ 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'Y/m/d', 'Y年m月d日', 'm-d', 'm月d日' ];
        foreach ( $formats as $fmt ) {
            $d = DateTime::createFromFormat( $fmt, $str );
            if ( $d ) return $d->format( 'Y-m-d H:i:s' );
        }
        return current_time( 'mysql' );
    }

    /* ---- robots.txt 检查（返回数组，包含详细说明）---- */
    private function check_robots( $url ) {
        $parsed     = parse_url( $url );
        $robots_url = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' ) . '/robots.txt';
        $resp       = wp_remote_get( $robots_url, [ 'timeout' => 5, 'sslverify' => false ] );

        if ( is_wp_error( $resp ) ) {
            return [ 'allowed' => true, 'note' => '无法获取 robots.txt，默认允许' ];
        }

        $body     = wp_remote_retrieve_body( $resp );
        $path     = $parsed['path'] ?? '/';
        $ua_match = false;

        foreach ( explode( "\n", $body ) as $line ) {
            $line = trim( $line );
            if ( stripos( $line, 'User-agent: *' ) === 0 ||
                 stripos( $line, 'User-agent: ChengduLifeBot' ) === 0 ) {
                $ua_match = true;
            }
            if ( $ua_match && stripos( $line, 'Disallow:' ) === 0 ) {
                $disallow = trim( substr( $line, 9 ) );
                if ( $disallow && strpos( $path, $disallow ) === 0 ) {
                    return [ 'allowed' => false, 'note' => "Disallow: $disallow" ];
                }
            }
            if ( $ua_match && stripos( $line, 'User-agent:' ) === 0 &&
                 stripos( $line, '*' ) === false &&
                 stripos( $line, 'ChengduLifeBot' ) === false ) {
                $ua_match = false;
            }
        }

        return [ 'allowed' => true, 'note' => 'robots.txt 检查通过' ];
    }
}
