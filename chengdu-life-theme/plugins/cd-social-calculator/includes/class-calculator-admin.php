<?php
/**
 * 五险一金计算器后台管理类
 * @package CD_Social_Calculator
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Calculator_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_post_cdsc_save', [ $this, 'save_settings' ] );
    }

    public function add_menu() {
        add_options_page( '五险一金设置', '五险一金设置', 'manage_options', 'cd-social-calculator', [ $this, 'render_page' ] );
    }

    public function render_page() {
        $fields = [
            'cdsc_base_min'          => [ '缴费基数下限（元）', 3979 ],
            'cdsc_base_max'          => [ '缴费基数上限（元）', 19897 ],
            'cdsc_base_default'      => [ '默认缴费基数（元）', 7000 ],
            'cdsc_pension_personal'  => [ '养老保险 - 个人比例（%）', 8 ],
            'cdsc_pension_company'   => [ '养老保险 - 单位比例（%）', 16 ],
            'cdsc_medical_personal'  => [ '医疗保险 - 个人比例（%）', 2 ],
            'cdsc_medical_company'   => [ '医疗保险 - 单位比例（%）', 7.5 ],
            'cdsc_unemploy_personal' => [ '失业保险 - 个人比例（%）', 0.5 ],
            'cdsc_unemploy_company'  => [ '失业保险 - 单位比例（%）', 0.5 ],
            'cdsc_injury_company'    => [ '工伤保险 - 单位比例（%）', 0.5 ],
            'cdsc_maternity_company' => [ '生育保险 - 单位比例（%）', 0.8 ],
            'cdsc_fund_personal'     => [ '公积金 - 个人默认比例（%）', 12 ],
            'cdsc_fund_company'      => [ '公积金 - 单位比例（%）', 12 ],
            'cdsc_fund_min_rate'     => [ '公积金 - 最低比例（%）', 5 ],
            'cdsc_tax_threshold'     => [ '个税起征点（元）', 5000 ],
        ];
        ?>
        <div class="wrap">
            <h1>💰 成都五险一金计算器设置</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field( 'cdsc_save', 'cdsc_nonce' ); ?>
                <input type="hidden" name="action" value="cdsc_save">
                <table class="form-table">
                    <?php foreach ( $fields as $key => [ $label, $default ] ) : ?>
                    <tr>
                        <th><?php echo esc_html( $label ); ?></th>
                        <td><input type="number" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr( get_option($key, $default) ); ?>" step="0.01" class="regular-text"></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p><strong>短代码：</strong><code>[cd_social_calc]</code></p>
                <?php submit_button('保存设置'); ?>
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '权限不足' );
        check_admin_referer( 'cdsc_save', 'cdsc_nonce' );
        $keys = [ 'cdsc_base_min','cdsc_base_max','cdsc_base_default','cdsc_pension_personal','cdsc_pension_company','cdsc_medical_personal','cdsc_medical_company','cdsc_unemploy_personal','cdsc_unemploy_company','cdsc_injury_company','cdsc_maternity_company','cdsc_fund_personal','cdsc_fund_company','cdsc_fund_min_rate','cdsc_tax_threshold' ];
        foreach ( $keys as $k ) {
            if ( isset( $_POST[$k] ) ) update_option( $k, floatval( $_POST[$k] ) );
        }
        wp_redirect( admin_url( 'options-general.php?page=cd-social-calculator&updated=1' ) );
        exit;
    }
}
