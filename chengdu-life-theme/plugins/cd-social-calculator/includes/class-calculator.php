<?php
/**
 * 五险一金计算核心类
 * @package CD_Social_Calculator
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Social_Calculator {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts',     [ $this, 'enqueue_assets' ] );
        add_shortcode( 'cd_social_calc',      [ $this, 'render_shortcode' ] );
        add_action( 'wp_ajax_cdsc_calculate',        [ $this, 'ajax_calculate' ] );
        add_action( 'wp_ajax_nopriv_cdsc_calculate', [ $this, 'ajax_calculate' ] );
    }

    public function enqueue_assets() {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'cd_social_calc' ) ) return;

        wp_enqueue_style( 'cd-social-calc', CDSC_URI . 'assets/css/social-calc.css', [], CDSC_VERSION );
        wp_enqueue_script( 'cd-social-calc', CDSC_URI . 'assets/js/social-calc.js', [ 'jquery' ], CDSC_VERSION, true );
        wp_localize_script( 'cd-social-calc', 'cdscData', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cdsc_nonce' ),
            'baseMin'  => (float) get_option( 'cdsc_base_min', 3979 ),
            'baseMax'  => (float) get_option( 'cdsc_base_max', 19897 ),
            'defaults' => $this->get_rates(),
        ] );
    }

    /* ---- 获取所有费率配置 ---- */
    public function get_rates() {
        return [
            'base_min'          => (float) get_option( 'cdsc_base_min', 3979 ),
            'base_max'          => (float) get_option( 'cdsc_base_max', 19897 ),
            'base_default'      => (float) get_option( 'cdsc_base_default', 7000 ),
            'pension_personal'  => (float) get_option( 'cdsc_pension_personal', 8 ),
            'pension_company'   => (float) get_option( 'cdsc_pension_company', 16 ),
            'medical_personal'  => (float) get_option( 'cdsc_medical_personal', 2 ),
            'medical_company'   => (float) get_option( 'cdsc_medical_company', 7.5 ),
            'unemploy_personal' => (float) get_option( 'cdsc_unemploy_personal', 0.5 ),
            'unemploy_company'  => (float) get_option( 'cdsc_unemploy_company', 0.5 ),
            'injury_company'    => (float) get_option( 'cdsc_injury_company', 0.5 ),
            'maternity_company' => (float) get_option( 'cdsc_maternity_company', 0.8 ),
            'fund_personal'     => (float) get_option( 'cdsc_fund_personal', 12 ),
            'fund_company'      => (float) get_option( 'cdsc_fund_company', 12 ),
            'fund_min_rate'     => (float) get_option( 'cdsc_fund_min_rate', 5 ),
            'tax_threshold'     => (float) get_option( 'cdsc_tax_threshold', 5000 ),
        ];
    }

    /* ---- 核心计算逻辑 ---- */
    public function calculate( $salary, $base = null, $fund_rate_personal = null ) {
        $rates = $this->get_rates();

        // 缴费基数：不超过上下限
        if ( $base === null ) $base = $salary;
        $base = max( $rates['base_min'], min( $rates['base_max'], (float) $base ) );

        // 公积金比例（个人可自定义，最低5%）
        $fund_rate = $fund_rate_personal !== null
            ? max( $rates['fund_min_rate'], min( 24, (float) $fund_rate_personal ) )
            : $rates['fund_personal'];

        // ---- 个人缴纳 ----
        $pension_p  = round( $base * $rates['pension_personal']  / 100, 2 );
        $medical_p  = round( $base * $rates['medical_personal']  / 100, 2 );
        $unemploy_p = round( $base * $rates['unemploy_personal'] / 100, 2 );
        $fund_p     = round( $base * $fund_rate                  / 100, 2 );

        $total_personal = round( $pension_p + $medical_p + $unemploy_p + $fund_p, 2 );

        // ---- 单位缴纳 ----
        $pension_c  = round( $base * $rates['pension_company']   / 100, 2 );
        $medical_c  = round( $base * $rates['medical_company']   / 100, 2 );
        $unemploy_c = round( $base * $rates['unemploy_company']  / 100, 2 );
        $injury_c   = round( $base * $rates['injury_company']    / 100, 2 );
        $maternity_c= round( $base * $rates['maternity_company'] / 100, 2 );
        $fund_c     = round( $base * $rates['fund_company']      / 100, 2 );

        $total_company = round( $pension_c + $medical_c + $unemploy_c + $injury_c + $maternity_c + $fund_c, 2 );

        // ---- 个人所得税（2024年综合所得税率表）----
        $taxable = round( (float)$salary - $total_personal - $rates['tax_threshold'], 2 );
        $tax     = $taxable > 0 ? $this->calc_income_tax( $taxable ) : 0;

        // ---- 税后工资 ----
        $net_salary = round( (float)$salary - $total_personal - $tax, 2 );

        // ---- 用人单位总成本 ----
        $total_cost = round( (float)$salary + $total_company, 2 );

        return [
            'salary'          => (float) $salary,
            'base'            => $base,
            'fund_rate'       => $fund_rate,
            'personal' => [
                'pension'   => $pension_p,
                'medical'   => $medical_p,
                'unemploy'  => $unemploy_p,
                'fund'      => $fund_p,
                'total'     => $total_personal,
            ],
            'company' => [
                'pension'   => $pension_c,
                'medical'   => $medical_c,
                'unemploy'  => $unemploy_c,
                'injury'    => $injury_c,
                'maternity' => $maternity_c,
                'fund'      => $fund_c,
                'total'     => $total_company,
            ],
            'taxable'     => max( 0, $taxable ),
            'tax'         => $tax,
            'net_salary'  => $net_salary,
            'total_cost'  => $total_cost,
            'rates'       => $rates,
        ];
    }

    /* ---- 个税计算（月度累计预扣法简化版）---- */
    private function calc_income_tax( $taxable ) {
        // 2024年个人所得税税率表（月应纳税所得额）
        $brackets = [
            [ 'limit' => 3000,  'rate' => 3,  'deduction' => 0 ],
            [ 'limit' => 12000, 'rate' => 10, 'deduction' => 210 ],
            [ 'limit' => 25000, 'rate' => 20, 'deduction' => 1410 ],
            [ 'limit' => 35000, 'rate' => 25, 'deduction' => 2660 ],
            [ 'limit' => 55000, 'rate' => 30, 'deduction' => 4410 ],
            [ 'limit' => 80000, 'rate' => 35, 'deduction' => 7160 ],
            [ 'limit' => PHP_INT_MAX, 'rate' => 45, 'deduction' => 15160 ],
        ];

        foreach ( $brackets as $b ) {
            if ( $taxable <= $b['limit'] ) {
                return round( $taxable * $b['rate'] / 100 - $b['deduction'], 2 );
            }
        }
        return 0;
    }

    /* ---- AJAX 处理 ---- */
    public function ajax_calculate() {
        check_ajax_referer( 'cdsc_nonce', 'nonce' );

        $salary    = floatval( $_POST['salary']    ?? 0 );
        $base      = ! empty( $_POST['base'] ) ? floatval( $_POST['base'] ) : null;
        $fund_rate = ! empty( $_POST['fund_rate'] ) ? floatval( $_POST['fund_rate'] ) : null;

        if ( $salary <= 0 ) {
            wp_send_json_error( '请输入有效的税前工资' );
        }

        $result = $this->calculate( $salary, $base, $fund_rate );
        wp_send_json_success( $result );
    }

    /* ---- 短代码渲染 ---- */
    public function render_shortcode( $atts ) {
        $rates = $this->get_rates();
        ob_start();
        include CDSC_DIR . 'includes/template-calculator.php';
        return ob_get_clean();
    }
}
