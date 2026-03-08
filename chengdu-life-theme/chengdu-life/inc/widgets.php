<?php
/**
 * 主题自定义小组件
 * @package ChengduLife
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
   热门话题小组件
   ============================================================ */
class CDL_Hot_Topics_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct( 'cdl_hot_topics', '热门话题', [ 'description' => '显示热门文章列表' ] );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '热门话题';
        $count = ! empty( $instance['count'] ) ? (int) $instance['count'] : 8;

        echo $args['before_widget'];
        ?>
        <div class="sidebar-widget">
            <div class="sidebar-widget-header">
                <i class="fas fa-fire"></i>
                <?php echo esc_html( $title ); ?>
            </div>
            <div class="sidebar-widget-body">
                <?php
                $hot_posts = new WP_Query( [
                    'posts_per_page' => $count,
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
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '热门话题';
        $count = ! empty( $instance['count'] ) ? $instance['count'] : 8;
        ?>
        <p>
            <label>标题：<input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>"></label>
        </p>
        <p>
            <label>显示数量：<input class="widefat" name="<?php echo $this->get_field_name('count'); ?>" type="number" value="<?php echo esc_attr($count); ?>" min="1" max="20"></label>
        </p>
        <?php
    }

    public function update( $new, $old ) {
        $new['title'] = sanitize_text_field( $new['title'] );
        $new['count'] = absint( $new['count'] );
        return $new;
    }
}

/* ============================================================
   天气小组件（静态展示，可接入 API）
   ============================================================ */
class CDL_Weather_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct( 'cdl_weather', '成都天气', [ 'description' => '显示成都天气信息' ] );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        // 天气数据可通过 Options API 缓存，此处为静态示例
        $weather = get_transient( 'cdl_weather_data' );
        if ( ! $weather ) {
            $weather = [ 'temp' => '--', 'desc' => '获取中', 'icon' => '🌤️', 'high' => '--', 'low' => '--', 'humidity' => '--', 'aqi' => '--' ];
        }
        ?>
        <div class="sidebar-widget">
            <div class="sidebar-widget-header">
                <i class="fas fa-cloud-sun"></i> 成都天气
            </div>
            <div class="sidebar-widget-body">
                <div class="weather-widget">
                    <div class="weather-city">成都市 · 实时天气</div>
                    <div class="weather-main">
                        <span class="weather-icon"><?php echo esc_html( $weather['icon'] ); ?></span>
                        <span class="weather-temp"><?php echo esc_html( $weather['temp'] ); ?>°</span>
                    </div>
                    <div class="weather-desc"><?php echo esc_html( $weather['desc'] ); ?></div>
                    <div class="weather-detail">
                        <span><i class="fas fa-temperature-high"></i> <?php echo esc_html( $weather['high'] ); ?>°</span>
                        <span><i class="fas fa-temperature-low"></i> <?php echo esc_html( $weather['low'] ); ?>°</span>
                        <span><i class="fas fa-tint"></i> <?php echo esc_html( $weather['humidity'] ); ?>%</span>
                        <span>AQI <?php echo esc_html( $weather['aqi'] ); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        echo '<p>天气数据通过后台定时任务自动更新，无需手动配置。</p>';
    }

    public function update( $new, $old ) { return $new; }
}

/* ============================================================
   注册小组件
   ============================================================ */
function cdl_register_widgets() {
    register_widget( 'CDL_Hot_Topics_Widget' );
    register_widget( 'CDL_Weather_Widget' );
}
add_action( 'widgets_init', 'cdl_register_widgets' );
