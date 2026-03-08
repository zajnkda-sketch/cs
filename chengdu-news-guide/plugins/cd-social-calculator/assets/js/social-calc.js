/**
 * 成都五险一金计算工具 - 前端脚本
 * Plugin: CD Social Calculator
 */

(function ($) {
    'use strict';

    $('#calc-btn').on('click', function () {
        var grossSalary      = parseFloat($('#gross-salary').val()) || 0;
        var socialBase       = parseFloat($('#social-base').val()) || 0;
        var gjjRate          = parseFloat($('#gjj-rate').val()) || 0.12;
        var specialDeduction = parseFloat($('#special-deduction').val()) || 0;

        if (grossSalary <= 0) {
            alert('请输入有效的税前月工资');
            return;
        }

        var $btn    = $(this);
        var $result = $('#calc-result');

        $btn.prop('disabled', true).text('计算中...');

        $.ajax({
            url: cdSocialCalc.ajaxUrl,
            type: 'POST',
            data: {
                action:            'cd_calculate_social',
                nonce:             cdSocialCalc.nonce,
                gross_salary:      grossSalary,
                social_base:       socialBase,
                gjj_rate:          gjjRate,
                special_deduction: specialDeduction,
            },
            success: function (response) {
                if (response.success) {
                    var data = response.data;

                    // 更新汇总数据
                    $('#net-salary').text('¥ ' + data.net_salary.toFixed(2));
                    $('#personal-total').text('¥ ' + data.personal_total.toFixed(2));
                    $('#company-total').text('¥ ' + data.company_total.toFixed(2));
                    $('#income-tax').text('¥ ' + data.income_tax.toFixed(2));

                    // 更新明细表格
                    var detailHtml = '';
                    $.each(data.details, function (i, item) {
                        detailHtml +=
                            '<tr>' +
                            '<td>' + item.name + '</td>' +
                            '<td>¥ ' + item.base.toFixed(2) + '</td>' +
                            '<td>' + item.personal_rate + '</td>' +
                            '<td>¥ ' + item.personal_amount.toFixed(2) + '</td>' +
                            '<td>' + item.company_rate + '</td>' +
                            '<td>¥ ' + item.company_amount.toFixed(2) + '</td>' +
                            '</tr>';
                    });
                    $('#result-detail-body').html(detailHtml);

                    $result.slideDown();
                } else {
                    alert('计算失败：' + response.data);
                }
            },
            error: function () {
                alert('网络错误，请稍后重试');
            },
            complete: function () {
                $btn.prop('disabled', false).text('立即计算');
            },
        });
    });

})(jQuery);
