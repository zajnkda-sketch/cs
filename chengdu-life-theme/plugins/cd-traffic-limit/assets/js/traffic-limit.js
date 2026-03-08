/**
 * 成都限行查询 - 前端脚本
 */
(function ($) {
    'use strict';

    const rules = cdtData.rules;
    const time  = cdtData.time;
    const area  = cdtData.area;
    const dayNames = { 1:'周一', 2:'周二', 3:'周三', 4:'周四', 5:'周五', 6:'周六', 7:'周日' };

    /* ---- 更新今日日期显示 ---- */
    function updateDate() {
        const now = new Date();
        const days = ['日','一','二','三','四','五','六'];
        const str = now.getFullYear() + '年' + (now.getMonth()+1) + '月' + now.getDate() + '日 星期' + days[now.getDay()];
        $('#cdtTodayDate').text(str);
    }
    updateDate();

    /* ---- 车牌查询 ---- */
    $('#cdtCheckBtn').on('click', function () {
        const plate = $('#cdtPlateInput').val().trim();
        const date  = $('#cdtDateInput').val();
        const $btn  = $(this);
        const $res  = $('#cdtCheckResult');

        if (!plate) {
            $res.show().removeClass('cdt-result-limited cdt-result-free')
                .html('<p style="color:#999;">请输入车牌号码</p>');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 查询中...');

        $.post(cdtData.ajaxUrl, {
            action:     'cdt_query',
            sub_action: 'check_plate',
            plate:      plate,
            date:       date,
            nonce:      cdtData.nonce
        }, function (res) {
            $btn.prop('disabled', false).html('<i class="fas fa-search"></i> 立即查询');
            if (!res.success) {
                $res.show().html('<p style="color:#c62828;">' + res.data + '</p>');
                return;
            }
            const d = res.data;
            $res.show().removeClass('cdt-result-limited cdt-result-free');

            if (!d.is_workday) {
                $res.addClass('cdt-result-free').html(
                    '<div class="cdt-result-title">🎉 该日不限行</div>' +
                    '<div class="cdt-result-desc">查询日期为周末或法定节假日，全天不限行。</div>'
                );
            } else if (d.is_limited) {
                $res.addClass('cdt-result-limited').html(
                    '<div class="cdt-result-title">⚠️ 该车辆限行</div>' +
                    '<div class="cdt-result-desc">' +
                    '车牌 <strong>' + d.plate + '</strong> 尾号为 <strong>' + d.tail + '</strong>，' +
                    '在 ' + d.date + ' 限行。<br>' +
                    '限行时间：' + time + '<br>' +
                    '限行区域：' + area +
                    '</div>'
                );
            } else {
                $res.addClass('cdt-result-free').html(
                    '<div class="cdt-result-title">✅ 该车辆不限行</div>' +
                    '<div class="cdt-result-desc">' +
                    '车牌 <strong>' + d.plate + '</strong> 尾号为 <strong>' + d.tail + '</strong>，' +
                    '在 ' + d.date + ' 不限行，可正常上路行驶。' +
                    '</div>'
                );
            }
        });
    });

    /* ---- 回车触发查询 ---- */
    $('#cdtPlateInput').on('keydown', function (e) {
        if (e.key === 'Enter') $('#cdtCheckBtn').trigger('click');
    });

})(jQuery);
