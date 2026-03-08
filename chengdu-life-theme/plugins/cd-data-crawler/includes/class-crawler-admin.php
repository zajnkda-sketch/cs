<?php
/**
 * 数据采集插件后台管理类
 * @package CD_Data_Crawler
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class CD_Crawler_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',                  [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts',       [ $this, 'enqueue_assets' ] );
        add_action( 'admin_post_cdcr_save_task',   [ $this, 'save_task' ] );
        add_action( 'admin_post_cdcr_delete_task', [ $this, 'delete_task' ] );
    }

    public function add_menu() {
        add_menu_page(
            '成都数据采集', '数据采集', 'manage_options',
            'cd-data-crawler', [ $this, 'render_tasks_page' ],
            'dashicons-download', 30
        );
        add_submenu_page(
            'cd-data-crawler', '采集任务', '采集任务',
            'manage_options', 'cd-data-crawler', [ $this, 'render_tasks_page' ]
        );
        add_submenu_page(
            'cd-data-crawler', '添加任务', '添加任务',
            'manage_options', 'cdcr-add-task', [ $this, 'render_add_task_page' ]
        );
        add_submenu_page(
            'cd-data-crawler', '采集日志', '采集日志',
            'manage_options', 'cdcr-logs', [ $this, 'render_logs_page' ]
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'cd-data-crawler' ) === false && strpos( $hook, 'cdcr-' ) === false ) return;
        wp_enqueue_style( 'cdcr-admin', CDCR_URI . 'assets/css/admin.css', [], CDCR_VERSION );
        wp_enqueue_script( 'cdcr-admin', CDCR_URI . 'assets/js/admin.js', [ 'jquery' ], CDCR_VERSION, true );
        wp_localize_script( 'cdcr-admin', 'cdcrData', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cdcr_nonce' ),
        ] );
    }

    /* ---- 任务列表页 ---- */
    public function render_tasks_page() {
        $tasks = CD_Crawler_DB::get_tasks();
        ?>
        <div class="wrap cdcr-wrap">
            <h1 class="wp-heading-inline">🕷️ 数据采集任务</h1>
            <a href="<?php echo admin_url('admin.php?page=cdcr-add-task'); ?>" class="page-title-action">添加任务</a>
            <hr class="wp-header-end">

            <?php if ( isset($_GET['msg']) ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( urldecode($_GET['msg']) ); ?></p></div>
            <?php endif; ?>

            <div class="cdcr-notice">
                <strong>⚠️ 合规提示：</strong>本插件仅用于采集官方网站公开发布的信息。
                使用前请确认目标网站的 robots.txt 及使用条款，遵守相关法律法规。
            </div>

            <table class="wp-list-table widefat fixed striped cdcr-table">
                <thead>
                    <tr>
                        <th width="40">ID</th>
                        <th>任务名称</th>
                        <th>采集来源</th>
                        <th>分类</th>
                        <th>间隔</th>
                        <th>状态</th>
                        <th>上次运行</th>
                        <th>新增文章</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( $tasks ) : foreach ( $tasks as $task ) :
                    $logs = CD_Crawler_DB::get_logs( $task->id, 1 );
                    $last_log = $logs ? $logs[0] : null;
                    $cat = $task->category_id ? get_category( $task->category_id ) : null;
                ?>
                <tr>
                    <td><?php echo esc_html( $task->id ); ?></td>
                    <td><strong><?php echo esc_html( $task->name ); ?></strong></td>
                    <td><a href="<?php echo esc_url( $task->source_url ); ?>" target="_blank" style="max-width:200px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html( $task->source_url ); ?></a></td>
                    <td><?php echo $cat ? esc_html( $cat->name ) : '—'; ?></td>
                    <td><?php echo $this->format_interval( $task->interval ); ?></td>
                    <td>
                        <span class="cdcr-status cdcr-status-<?php echo $task->is_active ? 'active' : 'paused'; ?>">
                            <?php echo $task->is_active ? '运行中' : '已暂停'; ?>
                        </span>
                    </td>
                    <td><?php echo $task->last_run ? esc_html( $task->last_run ) : '—'; ?></td>
                    <td><?php echo esc_html( $task->run_count ); ?> 次 / <?php echo $last_log ? esc_html( $last_log->new_count ) : 0; ?> 篇</td>
                    <td>
                        <button class="button button-small cdcr-run-now" data-id="<?php echo esc_attr( $task->id ); ?>">立即采集</button>
                        <a href="<?php echo admin_url( 'admin.php?page=cdcr-add-task&edit=' . $task->id ); ?>" class="button button-small">编辑</a>
                        <a href="<?php echo wp_nonce_url( admin_url('admin-post.php?action=cdcr_delete_task&id=' . $task->id), 'cdcr_delete_' . $task->id ); ?>"
                           class="button button-small button-link-delete" onclick="return confirm('确定删除此任务及其所有日志？')">删除</a>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="9" style="text-align:center;padding:30px;color:#999;">暂无采集任务，<a href="<?php echo admin_url('admin.php?page=cdcr-add-task'); ?>">点击添加</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ---- 添加/编辑任务页 ---- */
    public function render_add_task_page() {
        $edit_id = absint( $_GET['edit'] ?? 0 );
        $task    = $edit_id ? CD_Crawler_DB::get_task( $edit_id ) : null;
        $cats    = get_categories( [ 'hide_empty' => false ] );
        $t       = (object) array_merge( [
            'id' => 0, 'name' => '', 'source_url' => '', 'list_sel' => '',
            'title_sel' => '', 'link_sel' => '', 'content_sel' => '',
            'date_sel' => '', 'img_sel' => '', 'category_id' => 0,
            'interval' => 3600, 'is_active' => 1,
        ], $task ? (array) $task : [] );
        ?>
        <div class="wrap cdcr-wrap">
            <h1><?php echo $edit_id ? '编辑采集任务' : '添加采集任务'; ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field( 'cdcr_save_task', 'cdcr_nonce' ); ?>
                <input type="hidden" name="action" value="cdcr_save_task">
                <input type="hidden" name="task_id" value="<?php echo esc_attr( $t->id ); ?>">

                <div class="cdcr-form-grid">
                    <div class="cdcr-form-section">
                        <h2>基本信息</h2>
                        <table class="form-table">
                            <tr><th>任务名称 *</th><td><input type="text" name="name" value="<?php echo esc_attr($t->name); ?>" class="regular-text" required placeholder="例：成都政务网最新通知"></td></tr>
                            <tr><th>采集来源 URL *</th><td><input type="url" name="source_url" value="<?php echo esc_attr($t->source_url); ?>" class="large-text" required placeholder="https://www.chengdu.gov.cn/notice/"></td></tr>
                            <tr><th>发布分类</th>
                                <td><select name="category_id">
                                    <option value="0">— 不指定分类 —</option>
                                    <?php foreach ( $cats as $cat ) : ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected( $t->category_id, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
                                    <?php endforeach; ?>
                                </select></td>
                            </tr>
                            <tr><th>采集间隔</th>
                                <td><select name="interval">
                                    <option value="1800"  <?php selected($t->interval, 1800);  ?>>30分钟</option>
                                    <option value="3600"  <?php selected($t->interval, 3600);  ?>>1小时</option>
                                    <option value="7200"  <?php selected($t->interval, 7200);  ?>>2小时</option>
                                    <option value="21600" <?php selected($t->interval, 21600); ?>>6小时</option>
                                    <option value="43200" <?php selected($t->interval, 43200); ?>>12小时</option>
                                    <option value="86400" <?php selected($t->interval, 86400); ?>>24小时</option>
                                </select></td>
                            </tr>
                            <tr><th>启用任务</th><td><input type="checkbox" name="is_active" value="1" <?php checked($t->is_active, 1); ?>></td></tr>
                        </table>
                    </div>

                    <div class="cdcr-form-section">
                        <h2>CSS 选择器配置</h2>
                        <p class="description" style="margin-bottom:16px;">使用 CSS 选择器指定页面元素，支持 <code>.class</code>、<code>#id</code>、<code>tag.class</code> 等常见格式。</p>
                        <table class="form-table">
                            <tr><th>列表容器 * <span class="cdcr-tip">每条新闻的外层容器</span></th>
                                <td><input type="text" name="list_sel" value="<?php echo esc_attr($t->list_sel); ?>" class="regular-text" placeholder="例：.news-list li 或 .article-item"></td></tr>
                            <tr><th>标题选择器 *</th>
                                <td><input type="text" name="title_sel" value="<?php echo esc_attr($t->title_sel); ?>" class="regular-text" placeholder="例：h3 或 .title 或 a"></td></tr>
                            <tr><th>链接选择器</th>
                                <td><input type="text" name="link_sel" value="<?php echo esc_attr($t->link_sel); ?>" class="regular-text" placeholder="例：a（留空则自动取父级 a 标签）"></td></tr>
                            <tr><th>正文选择器</th>
                                <td><input type="text" name="content_sel" value="<?php echo esc_attr($t->content_sel); ?>" class="regular-text" placeholder="例：.article-content（留空则不采集详情页）"></td></tr>
                            <tr><th>日期选择器</th>
                                <td><input type="text" name="date_sel" value="<?php echo esc_attr($t->date_sel); ?>" class="regular-text" placeholder="例：.date 或 time"></td></tr>
                            <tr><th>图片选择器</th>
                                <td><input type="text" name="img_sel" value="<?php echo esc_attr($t->img_sel); ?>" class="regular-text" placeholder="例：img.thumb 或 .cover img"></td></tr>
                        </table>
                    </div>
                </div>

                <div class="cdcr-notice" style="margin-top:20px;">
                    <strong>📋 使用说明：</strong>
                    新采集的文章默认保存为<strong>草稿</strong>状态，需人工审核后方可发布。
                    建议先在浏览器中使用开发者工具（F12）确认 CSS 选择器，再填入此处。
                </div>

                <?php submit_button( $edit_id ? '保存修改' : '创建任务' ); ?>
            </form>
        </div>
        <?php
    }

    /* ---- 日志页 ---- */
    public function render_logs_page() {
        $task_id = absint( $_GET['task_id'] ?? 0 );
        $logs    = CD_Crawler_DB::get_logs( $task_id, 100 );
        $tasks   = CD_Crawler_DB::get_tasks();
        ?>
        <div class="wrap cdcr-wrap">
            <h1>📋 采集日志</h1>
            <div style="margin-bottom:16px;">
                <form method="get" style="display:inline-flex;gap:8px;align-items:center;">
                    <input type="hidden" name="page" value="cdcr-logs">
                    <select name="task_id">
                        <option value="0">— 全部任务 —</option>
                        <?php foreach ( $tasks as $t ) : ?>
                        <option value="<?php echo esc_attr($t->id); ?>" <?php selected($task_id, $t->id); ?>><?php echo esc_html($t->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button">筛选</button>
                </form>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>时间</th><th>任务</th><th>状态</th><th>新增</th><th>重复</th><th>详情</th></tr></thead>
                <tbody>
                <?php if ( $logs ) : foreach ( $logs as $log ) : ?>
                <tr>
                    <td><?php echo esc_html( $log->run_at ); ?></td>
                    <td><?php echo esc_html( $log->task_name ?? '—' ); ?></td>
                    <td><span class="cdcr-status cdcr-status-<?php echo esc_attr($log->status); ?>"><?php echo esc_html( $log->status ); ?></span></td>
                    <td><?php echo esc_html( $log->new_count ); ?></td>
                    <td><?php echo esc_html( $log->dup_count ); ?></td>
                    <td><?php echo esc_html( $log->message ); ?></td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="6" style="text-align:center;color:#999;padding:30px;">暂无日志记录</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ---- 保存任务 ---- */
    public function save_task() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '权限不足' );
        check_admin_referer( 'cdcr_save_task', 'cdcr_nonce' );

        $data = [
            'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
            'source_url'  => esc_url_raw( $_POST['source_url'] ?? '' ),
            'list_sel'    => sanitize_text_field( $_POST['list_sel'] ?? '' ),
            'title_sel'   => sanitize_text_field( $_POST['title_sel'] ?? '' ),
            'link_sel'    => sanitize_text_field( $_POST['link_sel'] ?? '' ),
            'content_sel' => sanitize_text_field( $_POST['content_sel'] ?? '' ),
            'date_sel'    => sanitize_text_field( $_POST['date_sel'] ?? '' ),
            'img_sel'     => sanitize_text_field( $_POST['img_sel'] ?? '' ),
            'category_id' => absint( $_POST['category_id'] ?? 0 ),
            'interval'    => absint( $_POST['interval'] ?? 3600 ),
            'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
        ];

        $task_id = absint( $_POST['task_id'] ?? 0 );
        if ( $task_id ) {
            CD_Crawler_DB::update_task( $task_id, $data );
            $msg = '任务已更新';
        } else {
            $data['next_run'] = current_time( 'mysql' );
            CD_Crawler_DB::insert_task( $data );
            $msg = '任务已创建';
        }

        wp_redirect( admin_url( 'admin.php?page=cd-data-crawler&msg=' . urlencode($msg) ) );
        exit;
    }

    /* ---- 删除任务 ---- */
    public function delete_task() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( '权限不足' );
        $id = absint( $_GET['id'] ?? 0 );
        check_admin_referer( 'cdcr_delete_' . $id );
        CD_Crawler_DB::delete_task( $id );
        wp_redirect( admin_url( 'admin.php?page=cd-data-crawler&msg=' . urlencode('任务已删除') ) );
        exit;
    }

    private function format_interval( $seconds ) {
        if ( $seconds < 3600 ) return ( $seconds / 60 ) . ' 分钟';
        if ( $seconds < 86400 ) return ( $seconds / 3600 ) . ' 小时';
        return ( $seconds / 86400 ) . ' 天';
    }
}
