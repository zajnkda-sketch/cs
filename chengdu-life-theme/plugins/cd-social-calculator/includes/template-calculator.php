<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="cdsc-wrap" id="cdscWrap">

    <!-- 标题 -->
    <div class="cdsc-header">
        <span class="cdsc-header-icon">💰</span>
        <div>
            <h2 class="cdsc-title">成都五险一金计算器</h2>
            <p class="cdsc-subtitle">基于 <?php echo date('Y'); ?> 年成都市最新社保缴费标准</p>
        </div>
        <div class="cdsc-standard-badge">
            缴费基数 <?php echo number_format($rates['base_min']); ?> ~ <?php echo number_format($rates['base_max']); ?> 元
        </div>
    </div>

    <!-- 输入区 -->
    <div class="cdsc-input-section">
        <div class="cdsc-input-grid">
            <div class="cdsc-field">
                <label class="cdsc-label">税前月工资（元）<span class="cdsc-required">*</span></label>
                <input type="number" id="cdscSalary" class="cdsc-input" placeholder="请输入税前月工资"
                       min="0" max="999999" step="100" value="<?php echo esc_attr($rates['base_default']); ?>">
            </div>
            <div class="cdsc-field">
                <label class="cdsc-label">
                    缴费基数（元）
                    <span class="cdsc-tip" title="不填则默认等于税前工资，将自动限制在上下限之间">?</span>
                </label>
                <input type="number" id="cdscBase" class="cdsc-input"
                       placeholder="默认等于税前工资"
                       min="<?php echo esc_attr($rates['base_min']); ?>"
                       max="<?php echo esc_attr($rates['base_max']); ?>">
            </div>
            <div class="cdsc-field">
                <label class="cdsc-label">
                    公积金缴纳比例（%）
                    <span class="cdsc-tip" title="最低5%，最高24%，默认12%">?</span>
                </label>
                <div class="cdsc-input-with-suffix">
                    <input type="number" id="cdscFundRate" class="cdsc-input"
                           placeholder="默认 <?php echo esc_attr($rates['fund_personal']); ?>%"
                           min="<?php echo esc_attr($rates['fund_min_rate']); ?>" max="24" step="1"
                           value="<?php echo esc_attr($rates['fund_personal']); ?>">
                    <span class="cdsc-suffix">%</span>
                </div>
            </div>
        </div>
        <button id="cdscCalcBtn" class="cdsc-btn-primary">
            <i class="fas fa-calculator"></i> 立即计算
        </button>
    </div>

    <!-- 结果区（默认隐藏） -->
    <div id="cdscResult" class="cdsc-result-section" style="display:none;">

        <!-- 核心数字汇总 -->
        <div class="cdsc-summary-grid">
            <div class="cdsc-summary-card cdsc-summary-net">
                <div class="cdsc-summary-label">税后实发工资</div>
                <div class="cdsc-summary-value" id="cdscNetSalary">--</div>
                <div class="cdsc-summary-sub">元/月</div>
            </div>
            <div class="cdsc-summary-card cdsc-summary-personal">
                <div class="cdsc-summary-label">个人缴纳合计</div>
                <div class="cdsc-summary-value" id="cdscPersonalTotal">--</div>
                <div class="cdsc-summary-sub">元/月</div>
            </div>
            <div class="cdsc-summary-card cdsc-summary-tax">
                <div class="cdsc-summary-label">个人所得税</div>
                <div class="cdsc-summary-value" id="cdscTax">--</div>
                <div class="cdsc-summary-sub">元/月</div>
            </div>
            <div class="cdsc-summary-card cdsc-summary-cost">
                <div class="cdsc-summary-label">企业用工总成本</div>
                <div class="cdsc-summary-value" id="cdscTotalCost">--</div>
                <div class="cdsc-summary-sub">元/月</div>
            </div>
        </div>

        <!-- 详细明细 -->
        <div class="cdsc-detail-grid">
            <!-- 个人缴纳明细 -->
            <div class="cdsc-detail-card">
                <h3 class="cdsc-detail-title"><i class="fas fa-user"></i> 个人缴纳明细</h3>
                <table class="cdsc-detail-table">
                    <thead><tr><th>项目</th><th>比例</th><th>金额（元）</th></tr></thead>
                    <tbody>
                        <tr><td>养老保险</td><td id="cdscPensionPRate">--</td><td id="cdscPensionP">--</td></tr>
                        <tr><td>医疗保险</td><td id="cdscMedicalPRate">--</td><td id="cdscMedicalP">--</td></tr>
                        <tr><td>失业保险</td><td id="cdscUnemployPRate">--</td><td id="cdscUnemployP">--</td></tr>
                        <tr><td>住房公积金</td><td id="cdscFundPRate">--</td><td id="cdscFundP">--</td></tr>
                        <tr class="cdsc-total-row"><td colspan="2"><strong>个人合计</strong></td><td id="cdscPersonalTotalDetail">--</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- 单位缴纳明细 -->
            <div class="cdsc-detail-card">
                <h3 class="cdsc-detail-title"><i class="fas fa-building"></i> 单位缴纳明细</h3>
                <table class="cdsc-detail-table">
                    <thead><tr><th>项目</th><th>比例</th><th>金额（元）</th></tr></thead>
                    <tbody>
                        <tr><td>养老保险</td><td id="cdscPensionCRate">--</td><td id="cdscPensionC">--</td></tr>
                        <tr><td>医疗保险</td><td id="cdscMedicalCRate">--</td><td id="cdscMedicalC">--</td></tr>
                        <tr><td>失业保险</td><td id="cdscUnemployCRate">--</td><td id="cdscUnemployC">--</td></tr>
                        <tr><td>工伤保险</td><td id="cdscInjuryCRate">--</td><td id="cdscInjuryC">--</td></tr>
                        <tr><td>生育保险</td><td id="cdscMaternityCRate">--</td><td id="cdscMaternityC">--</td></tr>
                        <tr><td>住房公积金</td><td id="cdscFundCRate">--</td><td id="cdscFundC">--</td></tr>
                        <tr class="cdsc-total-row"><td colspan="2"><strong>单位合计</strong></td><td id="cdscCompanyTotal">--</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 工资计算过程 -->
        <div class="cdsc-formula-card">
            <h3 class="cdsc-detail-title"><i class="fas fa-equals"></i> 计算过程</h3>
            <div class="cdsc-formula" id="cdscFormula"></div>
        </div>

        <!-- 免责声明 -->
        <p class="cdsc-disclaimer">
            <i class="fas fa-info-circle"></i>
            以上计算结果仅供参考，实际缴费金额以成都市人力资源和社会保障局公告为准。
            个税计算采用月度预扣法，未考虑专项附加扣除（子女教育、住房贷款利息等）。
        </p>
    </div>

</div><!-- .cdsc-wrap -->
