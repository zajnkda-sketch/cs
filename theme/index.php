<?php
/**
 * 首页模板 - 改进版本
 * 包含价格趋势图表和四列卡片布局
 */
get_header(); 
?>

<main id="main" class="site-main">
    <!-- 英雄区域 -->
    <section class="hero-section" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); padding: 80px 20px; text-align: center; color: white;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h1 style="font-size: 48px; margin-bottom: 20px; font-weight: bold;">化肥行业实时数据平台</h1>
            <p style="font-size: 18px; margin-bottom: 30px;">提供化肥行业最新价格行情、行业资讯和企业产品展示，助力企业生产决策</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="#latest-prices" style="background: white; color: #2ecc71; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: bold;">查看最新价格</a>
                <a href="#data-analysis" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 30px; border-radius: 5px; text-decoration: none; border: 2px solid white;">数据分析</a>
            </div>
        </div>
    </section>

    <!-- 统计卡片 -->
    <section style="max-width: 1200px; margin: -40px auto 60px; padding: 0 20px; position: relative; z-index: 10;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="background: white; padding: 30px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="font-size: 32px; color: #2ecc71; margin: 0;">100+</h3>
                <p style="color: #666; margin: 10px 0 0 0;">监测品种<br><small>覆盖主要化肥品种</small></p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="font-size: 32px; color: #3498db; margin: 0;">31</h3>
                <p style="color: #666; margin: 10px 0 0 0;">监测地区<br><small>覆盖全国所有省份</small></p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="font-size: 32px; color: #f39c12; margin: 0;">每日</h3>
                <p style="color: #666; margin: 10px 0 0 0;">数据更新<br><small>实时更新价格指数</small></p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 style="font-size: 32px; color: #e74c3c; margin: 0;">500+</h3>
                <p style="color: #666; margin: 10px 0 0 0;">企业覆盖<br><small>涵盖主要化肥生产企业</small></p>
            </div>
        </div>
    </section>

    <!-- 最新价格行情 -->
    <section id="latest-prices" style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <h2 style="font-size: 32px; margin: 0; color: #333;">最新价格行情</h2>
            <a href="#" style="color: #2ecc71; text-decoration: none; border: 2px solid #2ecc71; padding: 8px 20px; border-radius: 5px;">查看更多</a>
        </div>

        <!-- 改进：四列布局 -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <?php
            global $wpdb;
            
            // 查询数据库中的价格数据
            $items_t  = $wpdb->prefix . 'fertp_price_items';
            $points_t = $wpdb->prefix . 'fertp_price_points';
            
            // 获取最新的价格数据（JOIN两张新表，每个品种取最新一条）
            $prices = $wpdb->get_results("
                SELECT i.name AS fertilizer_type, i.region, p.price, 0 AS price_change, p.pdate AS date_recorded
                FROM {$items_t} i
                INNER JOIN {$points_t} p ON p.item_slug = i.slug
                INNER JOIN (
                    SELECT item_slug, MAX(pdate) AS max_date
                    FROM {$points_t}
                    GROUP BY item_slug
                ) latest ON latest.item_slug = p.item_slug AND latest.max_date = p.pdate
                WHERE i.category = 'spot'
                ORDER BY i.id ASC
                LIMIT 4
            ");
            
            if ($prices && !empty($prices)) {
                foreach ($prices as $price) {
                    $change_class = floatval($price->price_change) >= 0 ? 'up' : 'down';
                    $change_symbol = floatval($price->price_change) >= 0 ? '↑' : '↓';
                    $change_color = floatval($price->price_change) >= 0 ? '#e74c3c' : '#27ae60';
                    ?>
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #2ecc71;">
                        <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #333;"><?php echo esc_html($price->fertilizer_type); ?></h3>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 28px; font-weight: bold; color: #2ecc71;">¥<?php echo number_format(floatval($price->price), 2); ?></span>
                            <span style="color: <?php echo $change_color; ?>; font-weight: bold;"><?php echo $change_symbol; ?><?php echo esc_html($price->price_change); ?>%</span>
                        </div>
                        <p style="color: #999; margin: 0; font-size: 12px;">地区：<?php echo esc_html($price->region); ?></p>
                        <p style="color: #999; margin: 5px 0 0 0; font-size: 12px;">更新时间：<?php echo esc_html($price->date_recorded); ?></p>
                    </div>
                    <?php
                }
            } else {
                echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">暂无价格数据</div>';
            }
            ?>
        </div>
    </section>

    <!-- 价格趋势分析 -->
    <section id="data-analysis" style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        <h2 style="font-size: 32px; margin-bottom: 40px; color: #333;">价格趋势分析</h2>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: relative;">
            <div style="position: relative; height: 380px;">
            <canvas id="priceChart"></canvas>
            </div>
        </div>
    </section>

    <!-- 行业资讯 -->
    <section style="max-width: 1200px; margin: 60px auto; padding: 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <h2 style="font-size: 32px; margin: 0; color: #333;">行业资讯</h2>
            <a href="#" style="color: #2ecc71; text-decoration: none; border: 2px solid #2ecc71; padding: 8px 20px; border-radius: 5px;">查看更多</a>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <?php
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => 3,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    ?>
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0;"><a href="<?php the_permalink(); ?>" style="color: #333; text-decoration: none;"><?php the_title(); ?></a></h3>
                        <p style="color: #999; font-size: 12px; margin: 0 0 10px 0;"><?php echo get_the_date(); ?></p>
                        <p style="color: #666; margin: 0 0 15px 0;"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></p>
                        <a href="<?php the_permalink(); ?>" style="color: #2ecc71; text-decoration: none; font-weight: bold;">阅读全文 →</a>
                    </div>
                    <?php
                }
                wp_reset_postdata();
            }
            ?>
        </div>
    </section>

    <!-- 邮件订阅 -->
    <section style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); color: white; padding: 60px 20px; margin: 60px 0 0 0;">
        <div style="max-width: 600px; margin: 0 auto; text-align: center;">
            <h2 style="font-size: 32px; margin-bottom: 20px;">订阅价格更新</h2>
            <p style="font-size: 16px; margin-bottom: 30px;">获取每日最新的化肥价格行情和市场分析</p>
            <form style="display: flex; gap: 10px;">
                <input type="email" placeholder="您的邮箱地址" style="flex: 1; padding: 12px 15px; border: none; border-radius: 5px; font-size: 14px;">
                <button type="submit" style="background: white; color: #2ecc71; padding: 12px 30px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">订阅</button>
            </form>
        </div>
    </section>
</main>

<!-- 引入Chart.js库 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 获取价格数据并绘制图表
    const ctx = document.getElementById('priceChart');
    
    if (ctx) {
        // 从PHP获取价格数据
        <?php
        global $wpdb;
        $items_t  = $wpdb->prefix . 'fertp_price_items';
        $points_t = $wpdb->prefix . 'fertp_price_points';
        $fpu_t    = $wpdb->prefix . 'fpu_price_history';
        
        // 优先读取采集插件的fertp表，否则回退到fpu_price_history
        $use_fertp = $wpdb->get_var("SHOW TABLES LIKE '{$items_t}'");
        
        if ($use_fertp) {
            // 使用CASE标准化品种名，方便JS匹配
            $all_prices = $wpdb->get_results("
                SELECT 
                    CASE 
                        WHEN i.name LIKE '%尿素%' THEN '尿素'
                        WHEN i.name LIKE '%磷酸二铵%' THEN '磷酸二铵'
                        WHEN i.name LIKE '%氯化钾%' THEN '氯化钾'
                        WHEN i.name LIKE '%复合肥%' THEN '复合肥'
                        WHEN i.name LIKE '%磷酸一铵%' THEN '磷酸一铵'
                        ELSE i.name
                    END AS fertilizer_type,
                    '全国' AS region,
                    AVG(p.price) AS price,
                    DATE(p.pdate) AS date_recorded
                FROM {$items_t} i
                INNER JOIN {$points_t} p ON p.item_slug = i.slug
                WHERE i.name LIKE '%尿素%' OR i.name LIKE '%磷酸二铵%' OR i.name LIKE '%氯化钾%' OR i.name LIKE '%复合肥%'
                GROUP BY fertilizer_type, DATE(p.pdate)
                ORDER BY p.pdate ASC
            ");
        } else {
            $all_prices = $wpdb->get_results("
                SELECT fertilizer_type, region, price, DATE(date_recorded) AS date_recorded
                FROM {$fpu_t}
                WHERE region = '全国'
                ORDER BY date_recorded ASC
            ");
        }
        
        // 整理数据
        $data_by_type = array();
        $dates = array();
        
        foreach ($all_prices as $p) {
            $type_key = $p->fertilizer_type;
            if (!isset($data_by_type[$type_key])) {
                $data_by_type[$type_key] = array();
            }
            $date_str = is_string($p->date_recorded) ? substr($p->date_recorded, 0, 10) : date('Y-m-d', strtotime($p->date_recorded));
            $data_by_type[$type_key][] = array(
                'date' => $date_str,
                'price' => floatval($p->price)
            );
            if (!in_array($date_str, $dates)) {
                $dates[] = $date_str;
            }
        }
        
        // 只保留最近60天数据
        sort($dates);
        if (count($dates) > 60) {
            $dates = array_slice($dates, -60);
        }
        
        // 构建Chart.js datasets格式
        $colors = array(
            '尿素'     => array('border' => '#2ecc71', 'bg' => 'rgba(46,204,113,0.1)'),
            '磷酸二铵' => array('border' => '#3498db', 'bg' => 'rgba(52,152,219,0.1)'),
            '氯化钾'   => array('border' => '#e74c3c', 'bg' => 'rgba(231,76,60,0.1)'),
            '复合肥'   => array('border' => '#f39c12', 'bg' => 'rgba(243,156,18,0.1)'),
        );
        $chart_data = array();
        foreach ($data_by_type as $type_name => $type_records) {
            // 建立日期=>价格映射
            $price_map = array();
            foreach ($type_records as $rec) {
                $price_map[$rec['date']] = $rec['price'];
            }
            // 按$dates顺序填充，缺失日期用null
            $data_points = array();
            foreach ($dates as $d) {
                $data_points[] = isset($price_map[$d]) ? round(floatval($price_map[$d]), 2) : null;
            }
            $color = isset($colors[$type_name]) ? $colors[$type_name] : array('border' => '#95a5a6', 'bg' => 'rgba(149,165,166,0.1)');
            $chart_data[] = array(
                'label'           => $type_name,
                'data'            => $data_points,
                'borderColor'     => $color['border'],
                'backgroundColor' => $color['bg'],
                'borderWidth'     => 2,
                'pointRadius'     => 2,
                'tension'         => 0.3,
                'spanGaps'        => true,
            );
        }
        ?>
        
        const chartConfig = {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { $ts = strtotime($d); return $ts ? date('m-d', $ts) : $d; }, $dates)); ?>,
                datasets: <?php echo json_encode($chart_data); ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: window.innerWidth <= 768 ? 'bottom' : 'top',
                        labels: {
                            font: { size: window.innerWidth <= 768 ? 11 : 14 },
                            padding: 10,
                            usePointStyle: true,
                            boxWidth: 10
                        }
                    },
                    title: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: '价格（元/吨）'
                        },
                        ticks: {
                            font: { size: window.innerWidth <= 768 ? 10 : 12 }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: '日期'
                        },
                        ticks: {
                            font: { size: window.innerWidth <= 768 ? 10 : 12 },
                            maxRotation: window.innerWidth <= 768 ? 45 : 0,
                            minRotation: window.innerWidth <= 768 ? 45 : 0,
                            maxTicksLimit: window.innerWidth <= 768 ? 8 : 15
                        }
                    }
                }
            }
        };
        
        // 创建图表
        new Chart(ctx, chartConfig);
    }
});
</script>

<?php get_footer(); ?>
