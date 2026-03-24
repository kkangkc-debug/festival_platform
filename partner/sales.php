<?php
include_once('./_common.php');

$g5['title'] = '매출 통계';
include_once('./partner_head.php');

$rt_id = $partner_shop['rt_id'];

// 날짜 범위
$period = isset($_GET['period']) ? clean_xss_tags($_GET['period']) : 'today'; // today, week, month, custom
$start_date = isset($_GET['start_date']) ? clean_xss_tags($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? clean_xss_tags($_GET['end_date']) : '';

// 날짜 계산
if ($period == 'today') {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
} else if ($period == 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = date('Y-m-d');
} else if ($period == 'month') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// SQL 날짜 조건
$date_where = " AND DATE(ro_order_time) BETWEEN '{$start_date}' AND '{$end_date}' ";

// 기본 통계
$stats = sql_fetch("
    SELECT
        COUNT(*) as total_orders,
        SUM(ro_total_price) as total_sales,
        COUNT(CASE WHEN ro_status = 'completed' THEN 1 END) as completed_orders,
        SUM(CASE WHEN ro_status = 'completed' THEN ro_total_price ELSE 0 END) as completed_sales,
        COUNT(CASE WHEN ro_status = 'cancelled' THEN 1 END) as cancelled_orders,
        SUM(CASE WHEN ro_status = 'cancelled' THEN ro_total_price ELSE 0 END) as cancelled_sales
    FROM rain_restaurant_order
    WHERE rt_id = '{$rt_id}'
    AND ro_status IN ('pending', 'cooking', 'completed', 'cancelled')
    {$date_where}
");

// 일별 매출 (차트용)
$daily_sales = sql_query("
    SELECT
        DATE(ro_order_time) as sale_date,
        COUNT(*) as order_count,
        SUM(CASE WHEN ro_status = 'completed' THEN ro_total_price ELSE 0 END) as sales
    FROM rain_restaurant_order
    WHERE rt_id = '{$rt_id}'
    {$date_where}
    GROUP BY DATE(ro_order_time)
    ORDER BY sale_date ASC
");

// 인기 메뉴 TOP 10
$popular_menus = sql_query("
    SELECT
        rm.rm_name,
        SUM(roi.roi_quantity) as total_quantity,
        SUM(roi.roi_subtotal) as total_sales
    FROM rain_restaurant_order_item roi
    JOIN rain_restaurant_order ro ON roi.ro_id = ro.ro_id
    JOIN rain_restaurant_menu rm ON roi.rm_id = rm.rm_id
    WHERE ro.rt_id = '{$rt_id}'
    AND ro.ro_status = 'completed'
    {$date_where}
    GROUP BY rm.rm_id, rm.rm_name
    ORDER BY total_quantity DESC
    LIMIT 10
");

// 시간대별 주문 (오늘만)
$hourly_orders = array();
if ($period == 'today') {
    $hourly_res = sql_query("
        SELECT
            HOUR(ro_order_time) as hour,
            COUNT(*) as order_count
        FROM rain_restaurant_order
        WHERE rt_id = '{$rt_id}'
        AND DATE(ro_order_time) = '{$start_date}'
        GROUP BY HOUR(ro_order_time)
        ORDER BY hour
    ");
    while ($row = sql_fetch_array($hourly_res)) {
        $hourly_orders[$row['hour']] = $row['order_count'];
    }
}
?>

<h2 class="page_title">📊 매출 통계</h2>

<!-- 날짜 선택 -->
<div class="card">
    <form method="get" style="display: flex; gap: 8px; flex-wrap: wrap;">
        <input type="hidden" name="period" value="custom">
        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="frm_input" style="width: auto;">
        <span style="display: flex; align-items: center;">~</span>
        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="frm_input" style="width: auto;">
        <button type="submit" class="btn btn_primary btn_sm">조회</button>
    </form>
    <div style="display: flex; gap: 5px; margin-top: 10px;">
        <a href="./sales.php?period=today" class="btn <?php echo $period == 'today' ? 'btn_primary' : 'btn_outline'; ?> btn_sm">오늘</a>
        <a href="./sales.php?period=week" class="btn <?php echo $period == 'week' ? 'btn_primary' : 'btn_outline'; ?> btn_sm">최근7일</a>
        <a href="./sales.php?period=month" class="btn <?php echo $period == 'month' ? 'btn_primary' : 'btn_outline'; ?> btn_sm">최근30일</a>
    </div>
</div>

<!-- 기간 정보 -->
<div style="text-align: center; margin-bottom: 15px;">
    <span style="font-size: 13px; color: #888;">
        <?php echo $start_date; ?> ~ <?php echo $end_date; ?>
    </span>
</div>

<!-- 핵심 통계 -->
<div style="display: flex; gap: 10px; margin-bottom: 15px;">
    <div class="card" style="flex: 1;">
        <div class="stat_label">총 주문</div>
        <div class="stat_value"><?php echo number_format($stats['total_orders'] ?? 0); ?>건</div>
    </div>
    <div class="card" style="flex: 1;">
        <div class="stat_label">완료 주문</div>
        <div class="stat_value" style="color: #28a745;"><?php echo number_format($stats['completed_orders'] ?? 0); ?>건</div>
    </div>
    <div class="card" style="flex: 1;">
        <div class="stat_label">취소 주문</div>
        <div class="stat_value" style="color: #dc3545;"><?php echo number_format($stats['cancelled_orders'] ?? 0); ?>건</div>
    </div>
</div>

<!-- 매출 요약 -->
<div class="card">
    <div class="card_title">💰 매출 요약</div>
    <div class="stat_card">
        <div>
            <div class="stat_value"><?php echo number_format($stats['completed_sales'] ?? 0); ?>원</div>
            <div class="stat_label">완료 매출</div>
        </div>
        <div class="stat_icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #fff;">
            <i class="fa fa-won"></i>
        </div>
    </div>
    <table style="width: 100%; margin-top: 15px; font-size: 13px;">
        <tr>
            <td style="padding: 8px 0; color: #888;">총 예상 매출</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600;"><?php echo number_format($stats['total_sales'] ?? 0); ?>원</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #888;">완료 매출</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #28a745;"><?php echo number_format($stats['completed_sales'] ?? 0); ?>원</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #888;">취소 매출</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #dc3545;"><?php echo number_format($stats['cancelled_sales'] ?? 0); ?>원</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #888;">평균 주문 금액</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600;">
                <?php echo ($stats['completed_orders'] ?? 0) > 0 ? number_format($stats['completed_sales'] / $stats['completed_orders']) : 0; ?>원
            </td>
        </tr>
    </table>
</div>

<!-- 일별 매출 차트 -->
<?php
$daily_data = array();
while ($row = sql_fetch_array($daily_sales)) {
    $daily_data[] = $row;
}

if (!empty($daily_data)) {
?>
<div class="card">
    <div class="card_title">📈 일별 매출 추이</div>
    <div style="margin-top: 15px;">
        <?php
        $max_sales = 0;
        foreach ($daily_data as $d) {
            if ($d['sales'] > $max_sales) $max_sales = $d['sales'];
        }

        foreach ($daily_data as $d) {
            $bar_height = $max_sales > 0 ? ($d['sales'] / $max_sales * 100) : 0;
            $bar_width = 100 / count($daily_data);
        ?>
        <div style="display: inline-block; width: <?php echo $bar_width; ?>%; text-align: center; vertical-align: top;">
            <div style="height: 150px; display: flex; align-items: flex-end; justify-content: center; padding: 0 2px;">
                <div style="width: 100%; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); border-radius: 4px 4px 0 0; height: <?php echo $bar_height; ?>%; min-height: 2px; position: relative;" title="<?php echo number_format($d['sales']); ?>원">
                    <span style="position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 10px; color: #667eea; font-weight: 600; white-space: nowrap;">
                        <?php echo number_format($d['sales']); ?>
                    </span>
                </div>
            </div>
            <div style="font-size: 10px; color: #888; margin-top: 5px;">
                <?php echo date('m/d', strtotime($d['sale_date'])); ?>
            </div>
            <div style="font-size: 10px; color: #555; font-weight: 600;">
                <?php echo $d['order_count']; ?>건
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- 인기 메뉴 -->
<?php
$popular_data = array();
while ($row = sql_fetch_array($popular_menus)) {
    $popular_data[] = $row;
}

if (!empty($popular_data)) {
?>
<div class="card">
    <div class="card_title">🏆 인기 메뉴 TOP <?php echo count($popular_data); ?></div>
    <div style="margin-top: 15px;">
        <?php
        $max_qty = 0;
        foreach ($popular_data as $p) {
            if ($p['total_quantity'] > $max_qty) $max_qty = $p['total_quantity'];
        }

        $rank = 1;
        foreach ($popular_data as $p) {
            $bar_width = $max_qty > 0 ? ($p['total_quantity'] / $max_qty * 100) : 0;
        ?>
        <div style="margin-bottom: 15px;">
            <div style="display: flex; align-items: center; margin-bottom: 5px;">
                <span style="width: 24px; height: 24px; background: <?php echo $rank <= 3 ? '#667eea' : '#ddd'; ?>; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; margin-right: 10px;">
                    <?php echo $rank; ?>
                </span>
                <span style="flex: 1; font-weight: 600;"><?php echo get_text($p['rm_name']); ?></span>
                <span style="font-size: 13px; color: #888;"><?php echo number_format($p['total_quantity']); ?>개</span>
            </div>
            <div style="height: 8px; background: #f0f0f0; border-radius: 4px; overflow: hidden; margin-left: 34px;">
                <div style="height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); width: <?php echo $bar_width; ?>%;"></div>
            </div>
            <div style="font-size: 11px; color: #888; margin-left: 34px; margin-top: 3px;">
                매출: <?php echo number_format($p['total_sales']); ?>원
            </div>
        </div>
        <?php $rank++; } ?>
    </div>
</div>
<?php } ?>

<!-- 시간대별 주문 (오늘만) -->
<?php if ($period == 'today' && !empty($hourly_orders)) {
    $max_hourly = 0;
    foreach ($hourly_orders as $cnt) {
        if ($cnt > $max_hourly) $max_hourly = $cnt;
    }
?>
<div class="card">
    <div class="card_title">⏰ 시간대별 주문 (오늘)</div>
    <div style="margin-top: 15px; display: flex; align-items: flex-end; height: 100px; gap: 2px;">
        <?php for ($h = 0; $h < 24; $h++) {
            $count = isset($hourly_orders[$h]) ? $hourly_orders[$h] : 0;
            $bar_height = $max_hourly > 0 ? ($count / $max_hourly * 100) : 0;
        ?>
        <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
            <div style="width: 100%; background: <?php echo $count > 0 ? '#667eea' : '#f0f0f0'; ?>; border-radius: 2px 2px 0 0; height: <?php echo $bar_height; ?>%; min-height: 2px;" title="<?php echo $count; ?>건"></div>
            <div style="font-size: 9px; color: #888; margin-top: 2px; transform: scale(0.8);"><?php echo $h; ?></div>
        </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- 메모 -->
<div class="card" style="background: #f8f9fa;">
    <div style="font-size: 12px; color: #666; line-height: 1.6;">
        <i class="fa fa-info-circle"></i> <strong>안내</strong>
        <ul style="margin: 5px 0 0 20px; padding: 0;">
            <li>완료 매출: 주문이 '완료' 상태인 경우만 집계</li>
            <li>취소 매출: 주문이 '취소' 상태인 경우만 집계</li>
            <li>통계는 실시간으로 업데이트됩니다</li>
        </ul>
    </div>
</div>

<?php
include_once('./partner_tail.php');
?>
