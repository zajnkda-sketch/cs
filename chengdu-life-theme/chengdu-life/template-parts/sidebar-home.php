<?php
/**
 * 首页侧边栏局部模板
 * @package ChengduLife
 */

// ---- 今日限行小组件 ----
$today     = new DateTime( 'now', new DateTimeZone( 'Asia/Shanghai' ) );
$weekday   = (int) $today->format( 'N' ); // 1=周一 … 7=周日
$day_names = [ 1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日' ];

// 成都尾号限行规则（工作日）
$limit_rules = [
    1 => [ 1, 6 ],
    2 => [ 2, 7 ],
    3 => [ 3, 8 ],
    4 => [ 4, 9 ],
    5 => [ 5, 0 ],
];
$is_workday = $weekday <= 5;
$limit_nums = $is_workday ? ( $limit_rules[ $weekday ] ?? [] ) : [];
?>

<!-- 今日限行 -->
<div class="sidebar-widget">
    <div class="sidebar-widget-header">
        <i class="fas fa-car"></i> 今日限行
        <a href="<?php echo esc_url( home_url( '/traffic-limit' ) ); ?>"
           style="margin-left:auto;font-size:12px;color:var(--color-primary);font-weight:500;">查询工具 →</a>
    </div>
    <div class="sidebar-widget-body">
        <div class="limit-widget-today">
            <div class="limit-widget-date">
                <?php echo esc_html( $today->format( 'Y年m月d日' ) . ' 星期' . $day_names[ $weekday ] ); ?>
            </div>
            <?php if ( $is_workday && $limit_nums ) : ?>
            <div class="limit-numbers">
                <div class="limit-num"><?php echo $limit_nums[0]; ?></div>
                <span class="limit-and">和</span>
                <div class="limit-num"><?php echo $limit_nums[1]; ?></div>
            </div>
            <div class="limit-info">
                限行尾号：<strong><?php echo implode(' 和 ', $limit_nums); ?></strong><br>
                限行时间：07:30 - 20:00<br>
                限行区域：绕城高速（G4202）以内
            </div>
            <?php else : ?>
            <div class="limit-no-limit">🎉 今日不限行</div>
            <div class="limit-info" style="margin-top:8px;">周末及节假日不限行</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 热门话题 -->
<div class="sidebar-widget">
    <div class="sidebar-widget-header">
        <i class="fas fa-fire"></i> 热门话题
    </div>
    <div class="sidebar-widget-body">
        <?php
        $hot_posts = new WP_Query( [
            'posts_per_page' => 8,
            'meta_key'       => 'post_views_count',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ] );
        if ( $hot_posts->have_posts() ) :
            $rank = 1;
        ?>
        <div class="hot-topics-list">
            <?php while ( $hot_posts->have_posts() ) : $hot_posts->the_post(); ?>
            <div class="hot-topic-item">
                <span class="hot-topic-rank rank-<?php echo $rank; ?>"><?php echo $rank; ?></span>
                <a href="<?php the_permalink(); ?>" class="hot-topic-title"><?php the_title(); ?></a>
                <span class="hot-topic-count"><?php echo cdl_human_time_diff(); ?></span>
            </div>
            <?php $rank++; endwhile; wp_reset_postdata(); ?>
        </div>
        <?php else : ?>
        <p style="color:var(--color-text-muted);font-size:14px;">暂无热门话题</p>
        <?php endif; ?>
    </div>
</div>

<!-- 天气 -->
<div class="sidebar-widget">
    <div class="sidebar-widget-header">
        <i class="fas fa-cloud-sun"></i> 成都天气
    </div>
    <div class="sidebar-widget-body">
        <?php
        $weather = get_transient( 'cdl_weather_data' );
        if ( ! $weather ) {
            $weather = [ 'temp' => '--', 'desc' => '数据获取中', 'icon' => '🌤️', 'high' => '--', 'low' => '--', 'humidity' => '--', 'aqi' => '--' ];
        }
        ?>
        <div class="weather-widget">
            <div class="weather-city">成都市 · 实时天气</div>
            <div class="weather-main">
                <span class="weather-icon"><?php echo esc_html( $weather['icon'] ); ?></span>
                <span class="weather-temp"><?php echo esc_html( $weather['temp'] ); ?>°</span>
            </div>
            <div class="weather-desc"><?php echo esc_html( $weather['desc'] ); ?></div>
            <div class="weather-detail">
                <span>↑ <?php echo esc_html( $weather['high'] ); ?>°</span>
                <span>↓ <?php echo esc_html( $weather['low'] ); ?>°</span>
                <span>湿度 <?php echo esc_html( $weather['humidity'] ); ?>%</span>
                <span>AQI <?php echo esc_html( $weather['aqi'] ); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- 实用工具快捷入口 -->
<div class="sidebar-widget">
    <div class="sidebar-widget-header">
        <i class="fas fa-tools"></i> 实用工具
    </div>
    <div class="sidebar-widget-body" style="display:flex;flex-direction:column;gap:10px;">
        <?php
        $tools = [
            [ 'icon' => 'fa-car',        'name' => '限行查询',   'url' => home_url( '/traffic-limit' ),      'class' => 'cat-card-traffic' ],
            [ 'icon' => 'fa-shield-alt', 'name' => '五险一金',   'url' => home_url( '/social-calculator' ),  'class' => 'cat-card-social' ],
            [ 'icon' => 'fa-landmark',   'name' => '办事指南',   'url' => home_url( '/guide' ),              'class' => 'cat-card-gov' ],
            [ 'icon' => 'fa-subway',     'name' => '地铁线路',   'url' => 'https://www.cdmetro.cn/',         'class' => 'cat-card-travel' ],
        ];
        foreach ( $tools as $tool ) :
        ?>
        <a href="<?php echo esc_url( $tool['url'] ); ?>"
           style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius-sm);background:var(--color-bg-page);transition:all var(--transition);"
           onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform=''">
            <span class="cat-card <?php echo esc_attr( $tool['class'] ); ?>"
                  style="width:36px;height:36px;border-radius:8px;padding:0;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                <i class="fas <?php echo esc_attr( $tool['icon'] ); ?>" style="font-size:16px;"></i>
            </span>
            <span style="font-size:14px;font-weight:600;color:var(--color-text-primary);"><?php echo esc_html( $tool['name'] ); ?></span>
            <i class="fas fa-chevron-right" style="margin-left:auto;font-size:12px;color:var(--color-text-muted);"></i>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ( is_active_sidebar( 'sidebar-home' ) ) : ?>
    <?php dynamic_sidebar( 'sidebar-home' ); ?>
<?php endif; ?>
