<?php
/**
 * 采集任务定时调度类
 * @package CD_Data_Crawler
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Crawler_Scheduler {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'cdcr_run_tasks', [ $this, 'run_due_tasks' ] );
        add_action( 'wp_ajax_cdcr_run_now', [ $this, 'ajax_run_now' ] );

        // 注册自定义 Cron 间隔
        add_filter( 'cron_schedules', [ $this, 'add_cron_intervals' ] );

        // 确保 Cron 任务已注册
        if ( ! wp_next_scheduled( 'cdcr_run_tasks' ) ) {
            wp_schedule_event( time(), 'every_15_minutes', 'cdcr_run_tasks' );
        }
    }

    public function add_cron_intervals( $schedules ) {
        $schedules['every_15_minutes'] = [
            'interval' => 900,
            'display'  => '每15分钟',
        ];
        $schedules['every_30_minutes'] = [
            'interval' => 1800,
            'display'  => '每30分钟',
        ];
        return $schedules;
    }

    /* ---- 执行到期任务 ---- */
    public function run_due_tasks() {
        $tasks = CD_Crawler_DB::get_tasks( true );
        $now   = current_time( 'timestamp' );

        foreach ( $tasks as $task ) {
            $next_run = $task->next_run ? strtotime( $task->next_run ) : 0;
            if ( $next_run && $next_run > $now ) continue;

            $engine = new CD_Crawler_Engine( $task );
            $engine->run();
        }
    }

    /* ---- AJAX 手动触发 ---- */
    public function ajax_run_now() {
        check_ajax_referer( 'cdcr_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( '权限不足' );

        $task_id = absint( $_POST['task_id'] ?? 0 );
        $task    = CD_Crawler_DB::get_task( $task_id );
        if ( ! $task ) wp_send_json_error( '任务不存在' );

        $engine = new CD_Crawler_Engine( $task );
        $result = $engine->run();

        $logs = CD_Crawler_DB::get_logs( $task_id, 1 );
        $msg  = $logs ? $logs[0]->message : ( $result ? '采集完成' : '采集失败' );

        wp_send_json_success( [ 'message' => $msg ] );
    }
}
