/**
 * 成都五险一金计算器 - 前端脚本
 */
(function ($) {
    'use strict';

    const fmt = (n) => parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const pct = (r) => r + '%';

    $('#cdscCalcBtn').on('click', function () {
        const salary   = parseFloat($('#cdscSalary').val());
        const base     = $('#cdscBase').val();
        const fundRate = $('#cdscFundRate').val();
        const $btn     = $(this);

        if (!salary || salary <= 0) {
            alert('请输入有效的税前月工资');
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 计算中...');

        $.post(cdscData.ajaxUrl, {
            action:    'cdsc_calculate',
            salary:    salary,
            base:      base || '',
            fund_rate: fundRate || '',
            nonce:     cdscData.nonce
        }, function (res) {
            $btn.prop('disabled', false).html('<i class="fas fa-calculator"></i> 立即计算');
            if (!res.success) { alert(res.data); return; }

            const d = res.data;
            const r = d.rates;

            // 汇总
            $('#cdscNetSalary').text(fmt(d.net_salary));
            $('#cdscPersonalTotal').text(fmt(d.personal.total));
            $('#cdscTax').text(fmt(d.tax));
            $('#cdscTotalCost').text(fmt(d.total_cost));

            // 个人明细
            $('#cdscPensionPRate').text(pct(r.pension_personal));
            $('#cdscPensionP').text(fmt(d.personal.pension));
            $('#cdscMedicalPRate').text(pct(r.medical_personal));
            $('#cdscMedicalP').text(fmt(d.personal.medical));
            $('#cdscUnemployPRate').text(pct(r.unemploy_personal));
            $('#cdscUnemployP').text(fmt(d.personal.unemploy));
            $('#cdscFundPRate').text(pct(d.fund_rate));
            $('#cdscFundP').text(fmt(d.personal.fund));
            $('#cdscPersonalTotalDetail').text(fmt(d.personal.total));

            // 单位明细
            $('#cdscPensionCRate').text(pct(r.pension_company));
            $('#cdscPensionC').text(fmt(d.company.pension));
            $('#cdscMedicalCRate').text(pct(r.medical_company));
            $('#cdscMedicalC').text(fmt(d.company.medical));
            $('#cdscUnemployCRate').text(pct(r.unemploy_company));
            $('#cdscUnemployC').text(fmt(d.company.unemploy));
            $('#cdscInjuryCRate').text(pct(r.injury_company));
            $('#cdscInjuryC').text(fmt(d.company.injury));
            $('#cdscMaternityCRate').text(pct(r.maternity_company));
            $('#cdscMaternityC').text(fmt(d.company.maternity));
            $('#cdscFundCRate').text(pct(r.fund_company));
            $('#cdscFundC').text(fmt(d.company.fund));
            $('#cdscCompanyTotal').text(fmt(d.company.total));

            // 计算过程
            $('#cdscFormula').html(
                '<div class="formula-row"><span class="formula-label">税前月工资</span><span class="formula-value">' + fmt(d.salary) + ' 元</span></div>' +
                '<div class="formula-row"><span class="formula-label formula-minus">- 个人五险一金</span><span class="formula-value formula-minus">- ' + fmt(d.personal.total) + ' 元</span></div>' +
                '<div class="formula-row"><span class="formula-label formula-minus">- 个人所得税（应纳税所得额 ' + fmt(d.taxable) + ' 元）</span><span class="formula-value formula-minus">- ' + fmt(d.tax) + ' 元</span></div>' +
                '<div class="formula-row formula-equals"><span class="formula-label formula-equals">= 税后实发工资</span><span class="formula-value formula-equals">' + fmt(d.net_salary) + ' 元</span></div>'
            );

            $('#cdscResult').slideDown(300);
            $('html, body').animate({ scrollTop: $('#cdscResult').offset().top - 80 }, 400);
        });
    });

    // 回车触发
    $('#cdscSalary, #cdscBase, #cdscFundRate').on('keydown', function (e) {
        if (e.key === 'Enter') $('#cdscCalcBtn').trigger('click');
    });

})(jQuery);
