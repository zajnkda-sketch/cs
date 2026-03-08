/**
 * 数据采集插件后台脚本
 */
(function ($) {
    'use strict';

    // 立即采集按钮
    $(document).on('click', '.cdcr-run-now', function () {
        const $btn   = $(this);
        const taskId = $btn.data('id');

        if (!confirm('确定立即执行此采集任务？')) return;

        $btn.prop('disabled', true).text('采集中...');

        $.post(cdcrData.ajaxUrl, {
            action:  'cdcr_run_now',
            task_id: taskId,
            nonce:   cdcrData.nonce
        }, function (res) {
            $btn.prop('disabled', false).text('立即采集');
            if (res.success) {
                alert('✅ ' + res.data.message);
                location.reload();
            } else {
                alert('❌ ' + (res.data || '采集失败'));
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('立即采集');
            alert('请求失败，请检查网络连接');
        });
    });

})(jQuery);
