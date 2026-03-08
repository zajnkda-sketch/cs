<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="cdt-wrap" id="cdTrafficWrap">

    <!-- 今日限行卡片 -->
    <div class="cdt-today-card">
        <div class="cdt-today-header">
            <span class="cdt-today-icon">🚗</span>
            <div>
                <h2 class="cdt-today-title">成都今日限行</h2>
                <p class="cdt-today-date" id="cdtTodayDate"></p>
            </div>
            <div class="cdt-today-badge">实时</div>
        </div>

        <?php if ( $today['is_workday'] && ! empty( $today['nums'] ) ) : ?>
        <div class="cdt-today-nums">
            <?php foreach ( $today['nums'] as $num ) : ?>
            <div class="cdt-num-circle"><?php echo esc_html( $num ); ?></div>
            <?php endforeach; ?>
        </div>
        <div class="cdt-today-info">
            <div class="cdt-info-row"><i class="fas fa-clock"></i> 限行时间：<strong><?php echo esc_html( $time ); ?></strong></div>
            <div class="cdt-info-row"><i class="fas fa-map-marker-alt"></i> 限行区域：<strong><?php echo esc_html( $area ); ?></strong></div>
            <div class="cdt-info-row"><i class="fas fa-info-circle"></i> 尾号为 <strong><?php echo implode(' 和 ', $today['nums']); ?></strong> 的车辆今日限行</div>
        </div>
        <?php else : ?>
        <div class="cdt-no-limit">
            <span class="cdt-no-limit-icon">🎉</span>
            <h3>今日不限行</h3>
            <p><?php echo $today['is_workday'] ? '今日为特殊调休日，不限行' : '周末及法定节假日不限行'; ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- 车牌查询 -->
    <?php if ( $atts['show_checker'] === 'yes' ) : ?>
    <div class="cdt-checker-card">
        <h3 class="cdt-section-title"><i class="fas fa-search"></i> 车牌尾号查询</h3>
        <div class="cdt-checker-form">
            <div class="cdt-input-group">
                <span class="cdt-input-prefix">川A</span>
                <input type="text" id="cdtPlateInput" class="cdt-input"
                       placeholder="输入车牌号（如 12345）" maxlength="10">
            </div>
            <div class="cdt-input-group">
                <label class="cdt-label">查询日期（留空为今天）</label>
                <input type="date" id="cdtDateInput" class="cdt-input">
            </div>
            <button id="cdtCheckBtn" class="cdt-btn-primary">
                <i class="fas fa-search"></i> 立即查询
            </button>
        </div>
        <div id="cdtCheckResult" class="cdt-check-result" style="display:none;"></div>
    </div>
    <?php endif; ?>

    <!-- 近7天预览 -->
    <?php if ( $atts['show_preview'] === 'yes' ) : ?>
    <div class="cdt-preview-card">
        <h3 class="cdt-section-title"><i class="fas fa-calendar-week"></i> 近7天限行预览</h3>
        <div class="cdt-week-grid">
            <?php foreach ( $preview as $day ) : ?>
            <div class="cdt-day-item <?php echo $day['is_workday'] ? 'is-workday' : 'is-weekend'; ?> <?php echo $day['date'] === date('Y-m-d') ? 'is-today' : ''; ?>">
                <div class="cdt-day-label"><?php echo esc_html( $day['label'] ); ?></div>
                <div class="cdt-day-date"><?php echo esc_html( $day['date_fmt'] ); ?></div>
                <?php if ( $day['is_workday'] && ! empty( $day['nums'] ) ) : ?>
                <div class="cdt-day-nums">
                    <?php foreach ( $day['nums'] as $n ) : ?>
                    <span class="cdt-day-num"><?php echo esc_html( $n ); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="cdt-day-free">不限行</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 限行规则说明 -->
    <div class="cdt-rules-card">
        <h3 class="cdt-section-title"><i class="fas fa-book"></i> 限行规则说明</h3>
        <div class="cdt-rules-table-wrap">
            <table class="cdt-rules-table">
                <thead>
                    <tr>
                        <th>星期</th>
                        <th>限行尾号</th>
                        <th>限行时间</th>
                        <th>限行区域</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day_names = [ 1 => '周一', 2 => '周二', 3 => '周三', 4 => '周四', 5 => '周五' ];
                    $rules     = get_option( 'cdt_limit_rules', [1=>[1,6],2=>[2,7],3=>[3,8],4=>[4,9],5=>[5,0]] );
                    foreach ( $day_names as $d => $name ) :
                        $nums = $rules[ $d ] ?? [];
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $name ); ?></strong></td>
                        <td><span class="cdt-rule-nums"><?php echo implode('、', $nums); ?></span></td>
                        <td><?php echo esc_html( $time ); ?></td>
                        <td><?php echo esc_html( $area ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><strong>周六/周日</strong></td>
                        <td colspan="3" style="color:#2E7D32;font-weight:600;">不限行</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="cdt-disclaimer">
            <i class="fas fa-exclamation-circle"></i>
            以上信息仅供参考，实际限行规则以成都市公安局交通管理局官方公告为准。
            法定节假日及特殊情况请关注官方通知。
        </p>
    </div>

</div><!-- .cdt-wrap -->
