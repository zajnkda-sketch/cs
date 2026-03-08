<?php
/**
 * 数据采集插件 - 数据库操作类
 * @package CD_Data_Crawler
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Crawler_DB {

    /* ---- 建表 ---- */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // 采集任务表
        $sql_tasks = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cdcr_tasks (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(200)    NOT NULL DEFAULT '',
            source_url  TEXT            NOT NULL,
            list_sel    VARCHAR(500)    NOT NULL DEFAULT '',
            title_sel   VARCHAR(500)    NOT NULL DEFAULT '',
            link_sel    VARCHAR(500)    NOT NULL DEFAULT '',
            content_sel VARCHAR(500)    NOT NULL DEFAULT '',
            date_sel    VARCHAR(500)    NOT NULL DEFAULT '',
            img_sel     VARCHAR(500)    NOT NULL DEFAULT '',
            category_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            interval    INT UNSIGNED    NOT NULL DEFAULT 3600,
            is_active   TINYINT(1)      NOT NULL DEFAULT 1,
            last_run    DATETIME                 DEFAULT NULL,
            next_run    DATETIME                 DEFAULT NULL,
            run_count   INT UNSIGNED    NOT NULL DEFAULT 0,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";

        // 采集日志表
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cdcr_logs (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id    BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status     VARCHAR(20)     NOT NULL DEFAULT 'success',
            message    TEXT,
            new_count  INT UNSIGNED    NOT NULL DEFAULT 0,
            dup_count  INT UNSIGNED    NOT NULL DEFAULT 0,
            run_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY task_id (task_id),
            KEY run_at  (run_at)
        ) $charset;";

        // 采集内容去重哈希表
        $sql_hash = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cdcr_hashes (
            id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            task_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            hash    CHAR(32)        NOT NULL DEFAULT '',
            url     TEXT,
            created DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY hash_unique (task_id, hash)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_tasks );
        dbDelta( $sql_logs );
        dbDelta( $sql_hash );
    }

    /* ---- 任务 CRUD ---- */
    public static function get_tasks( $active_only = false ) {
        global $wpdb;
        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cdcr_tasks {$where} ORDER BY id DESC" );
    }

    public static function get_task( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cdcr_tasks WHERE id = %d", $id
        ) );
    }

    public static function insert_task( $data ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'cdcr_tasks', $data );
        return $wpdb->insert_id;
    }

    public static function update_task( $id, $data ) {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . 'cdcr_tasks', $data, [ 'id' => $id ] );
    }

    public static function delete_task( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'cdcr_tasks', [ 'id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'cdcr_hashes', [ 'task_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'cdcr_logs',   [ 'task_id' => $id ] );
    }

    /* ---- 日志 ---- */
    public static function insert_log( $task_id, $status, $message, $new_count = 0, $dup_count = 0 ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'cdcr_logs', [
            'task_id'   => $task_id,
            'status'    => $status,
            'message'   => $message,
            'new_count' => $new_count,
            'dup_count' => $dup_count,
            'run_at'    => current_time( 'mysql' ),
        ] );
    }

    public static function get_logs( $task_id = 0, $limit = 50 ) {
        global $wpdb;
        $where = $task_id ? $wpdb->prepare( 'WHERE task_id = %d', $task_id ) : '';
        return $wpdb->get_results(
            "SELECT l.*, t.name AS task_name FROM {$wpdb->prefix}cdcr_logs l
             LEFT JOIN {$wpdb->prefix}cdcr_tasks t ON l.task_id = t.id
             {$where} ORDER BY l.run_at DESC LIMIT {$limit}"
        );
    }

    /* ---- 去重哈希 ---- */
    public static function hash_exists( $task_id, $hash ) {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cdcr_hashes WHERE task_id = %d AND hash = %s",
            $task_id, $hash
        ) );
    }

    public static function insert_hash( $task_id, $hash, $url ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'cdcr_hashes', [
            'task_id' => $task_id,
            'hash'    => $hash,
            'url'     => $url,
            'created' => current_time( 'mysql' ),
        ] );
    }
}
