/**
 * 成都限行查询工具 - 前端脚本
 * Plugin: CD Traffic Limit
 */

(function ($) {
    'use strict';

    // 查询车牌是否限行
    $('#check-plate-btn').on('click', function () {
        var plate = $('#plate-number').val().trim();
        if (!plate) {
            alert('请输入车牌号');
            return;
        }

        var $btn    = $(this);
        var $result = $('#plate-query-result');

        $btn.prop('disabled', true).text('查询中...');
        $result.html('<p class="loading">正在查询，请稍候...</p>');

        $.ajax({
            url: cdTrafficLimit.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cd_check_plate',
                nonce:  cdTrafficLimit.nonce,
                plate:  plate,
            },
            success: function (response) {
                if (response.success) {
                    var data    = response.data;
                    var cssClass = data.is_limited ? 'result-limited' : 'result-free';
                    var icon     = data.is_limited ? '⚠️' : '✅';

                    $result.html(
                        '<div class="query-result-box ' + cssClass + '">' +
                        '<p class="result-message">' + icon + ' ' + data.message + '</p>' +
                        '</div>'
                    );
                } else {
                    $result.html('<p class="result-error">查询失败：' + response.data + '</p>');
                }
            },
            error: function () {
                $result.html('<p class="result-error">网络错误，请稍后重试</p>');
            },
            complete: function () {
                $btn.prop('disabled', false).text('查询');
            },
        });
    });

    // 支持按 Enter 键触发查询
    $('#plate-number').on('keypress', function (e) {
        if (e.which === 13) {
            $('#check-plate-btn').trigger('click');
        }
    });

})(jQuery);
