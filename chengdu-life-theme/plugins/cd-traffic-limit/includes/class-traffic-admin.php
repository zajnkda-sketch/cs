<?php
/**
 * 限行查询后台管理类
 * @package CD_Traffic_Limit
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Traffic_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_post_cdt_save_settings', [ $this, 'save_settings' ] );
    }

    public function add_menu() {
        add_options_page(
            '成都限行设置', '限行查询设置', 'manage_options',
            'cd-traffic-limit', [ $this, 'render_settings_page' ]
        );
    }

    public function render_settings_page() {
        $rules    = get_option( 'cdt_limit_rules', [1=>[1,6],2=>[2,7],3=>[3,8],4=>[4,9],5=>[5,0]] );
        $time     = get_option( 'cdt_limit_time', '07:30 - 20:00' );
        $area     = get_option( 'cdt_limit_area', '绕城高速（G4202）以内所有道路' );
        $holidays = implode( "\n", get_option( 'cdt_holidays', [] ) );
        $day_names = [ 1=>'周一', 2=>'周二', 3=>'周三', 4=>'周四', 5=>'周五' ];
        ?>
        <div class="wrap">
            <h1>🚗 成都限行查询设置</h1>
            <?php settings_errors( 'cdt_settings' ); ?>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field( 'cdt_save_settings', 'cdt_nonce' ); ?>
                <input type="hidden" name="action" value="cdt_save_settings">

                <table class="form-table">
                    <tr>
                        <th>限行时间</th>
                        <td><input type="text" name="cdt_limit_time" value="<?php echo esc_attr($time); ?>" class="regular-text" placeholder="07:30 - 20:00"></td>
                    </tr>
                    <tr>
                        <th>限行区域</th>
                        <td><input type="text" name="cdt_limit_area" value="<?php echo esc_attr($area); ?>" class="large-text"></td>
                    </tr>
                    <?php foreach ( $day_names as $d => $name ) : ?>
                    <tr>
                        <th><?php echo esc_html($name); ?> 限行尾号</th>
                        <td>
                            <input type="number" name="cdt_rules[<?php echo $d; ?>][]" value="<?php echo esc_attr($rules[$d][0] ?? ''); ?>" min="0" max="9" style="width:60px;"> 和
                            <input type="number" name="cdt_rules[<?php echo $d; ?>][]" value="<?php echo esc_attr($rules[$d][1] ?? ''); ?>" min="0" max="9" style="width:60px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th>节假日（不限行）</th>
                        <td>
                            <textarea name="cdt_holidays" rows="6" class="large-text" placeholder="每行一个日期，格式：2025-01-01"><?php echo esc_textarea($holidays); ?></textarea>
                            <p class="description">每行输入一个不限行的日期（格式：YYYY-MM-DD），如法定节假日。</p>
                        </td>
                    </tr>
                </table>

                <p><strong>短代码使用：</strong><code>[cd_traffic_limit]</code> 或 <code>[cd_traffic_limit show_preview="yes" show_checker="yes"]</code></p>
                <?php submit_button( '保存设置' ); ?>
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '权限不足' );
        check_admin_referer( 'cdt_save_settings', 'cdt_nonce' );

        update_option( 'cdt_limit_time', sanitize_text_field( $_POST['cdt_limit_time'] ?? '' ) );
        update_option( 'cdt_limit_area', sanitize_text_field( $_POST['cdt_limit_area'] ?? '' ) );

        $raw_rules = $_POST['cdt_rules'] ?? [];
        $rules = [];
        foreach ( $raw_rules as $day => $nums ) {
            $rules[ (int)$day ] = array_map( 'absint', (array)$nums );
        }
        update_option( 'cdt_limit_rules', $rules );

        $holidays_raw = sanitize_textarea_field( $_POST['cdt_holidays'] ?? '' );
        $holidays = array_filter( array_map( 'trim', explode( "\n", $holidays_raw ) ) );
        update_option( 'cdt_holidays', array_values( $holidays ) );

        add_settings_error( 'cdt_settings', 'saved', '设置已保存！', 'success' );
        set_transient( 'settings_errors', get_settings_errors(), 30 );

        wp_redirect( admin_url( 'options-general.php?page=cd-traffic-limit&settings-updated=1' ) );
        exit;
    }
}
