<?php
/**
 * Plugin Name:  成都五险一金计算器
 * Plugin URI:   https://github.com/zajnkda-sketch/cs
 * Description:  基于成都最新社保缴费标准的五险一金计算工具，支持个人/企业缴费明细、个税计算、税后工资。
 * Version:      1.0.0
 * Author:       Manus AI
 * Text Domain:  cd-social-calculator
 * License:      GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CDSC_VERSION', '1.0.0' );
define( 'CDSC_DIR',     plugin_dir_path( __FILE__ ) );
define( 'CDSC_URI',     plugin_dir_url( __FILE__ ) );

require_once CDSC_DIR . 'includes/class-calculator.php';
require_once CDSC_DIR . 'includes/class-calculator-admin.php';

function cdsc_init() {
    CD_Social_Calculator::get_instance();
    if ( is_admin() ) CD_Calculator_Admin::get_instance();
}
add_action( 'plugins_loaded', 'cdsc_init' );

register_activation_hook( __FILE__, function () {
    // 2025年成都社保缴费标准（单位：元/月）
    // 缴费基数上限：成都市上年度在岗职工月均工资的3倍
    // 缴费基数下限：成都市上年度在岗职工月均工资的60%
    if ( ! get_option( 'cdsc_base_min' ) ) update_option( 'cdsc_base_min', 3979 );   // 下限
    if ( ! get_option( 'cdsc_base_max' ) ) update_option( 'cdsc_base_max', 19897 );  // 上限
    if ( ! get_option( 'cdsc_base_default' ) ) update_option( 'cdsc_base_default', 7000 ); // 默认基数

    // 养老保险
    if ( ! get_option( 'cdsc_pension_personal' ) )  update_option( 'cdsc_pension_personal', 8 );   // 个人8%
    if ( ! get_option( 'cdsc_pension_company' ) )   update_option( 'cdsc_pension_company',  16 );  // 单位16%

    // 医疗保险
    if ( ! get_option( 'cdsc_medical_personal' ) )  update_option( 'cdsc_medical_personal', 2 );   // 个人2%
    if ( ! get_option( 'cdsc_medical_company' ) )   update_option( 'cdsc_medical_company',  7.5 ); // 单位7.5%

    // 失业保险
    if ( ! get_option( 'cdsc_unemploy_personal' ) ) update_option( 'cdsc_unemploy_personal', 0.5 );
    if ( ! get_option( 'cdsc_unemploy_company' ) )  update_option( 'cdsc_unemploy_company',  0.5 );

    // 工伤保险（单位承担，行业不同，默认0.5%）
    if ( ! get_option( 'cdsc_injury_company' ) )    update_option( 'cdsc_injury_company', 0.5 );

    // 生育保险（单位承担）
    if ( ! get_option( 'cdsc_maternity_company' ) ) update_option( 'cdsc_maternity_company', 0.8 );

    // 住房公积金
    if ( ! get_option( 'cdsc_fund_personal' ) )     update_option( 'cdsc_fund_personal', 12 );  // 个人12%
    if ( ! get_option( 'cdsc_fund_company' ) )      update_option( 'cdsc_fund_company',  12 );  // 单位12%
    if ( ! get_option( 'cdsc_fund_min_rate' ) )     update_option( 'cdsc_fund_min_rate', 5 );   // 最低5%

    // 个税起征点
    if ( ! get_option( 'cdsc_tax_threshold' ) )     update_option( 'cdsc_tax_threshold', 5000 );
} );
