<?php
define('_GNUBOARD_', true);
include_once('./_common.php');

$g5['title'] = '주문 관리';
include_once('./partner_head.php');

$rt_id = $partner_shop['rt_id'];

// 검색 조건
$s_status = isset($_GET['s_status']) ? clean_xss_tags($_GET['s_status']) : '';
$s_date = isset($_GET['s_date']) ? clean_xss_tags($_GET['s_date']) : date('Y-m-d');

// SQL 조건 구성
$sql_where = " WHERE ro.rt_id = '{$rt_id}' ";
if ($s_status) {
    $sql_where .= " AND ro.ro_status = '{$s_status}' ";
}
if ($s_date) {
    $sql_where .= " AND DATE(ro.ro_order_time) = '{$s_date}' ";
}

// 주문 목록 조회
$sql = "
    SELECT ro.*,
           GROUP_CONCAT(CONCAT(roi.roi_quantity, 'x ', roi.roi_menu_name) SEPARATOR ' | ') as menu_summary,
           SUM(roi.roi_quantity) as total_quantity
    FROM rain_restaurant_order ro
    LEFT JOIN rain_restaurant_order_item roi ON ro.ro_id = roi.ro_id
    {$sql_where}
    GROUP BY ro.ro_id
    ORDER BY ro.ro_order_time DESC
";
$result = sql_query($sql);

// 상태별 카운트
$status_counts = array(
    'pending' => 0,
    'cooking' => 0,
    'completed' => 0,
    'cancelled' => 0
);

$count_sql = "
    SELECT ro_status, COUNT(*) as cnt
    FROM rain_restaurant_order
    WHERE rt_id = '{$rt_id}'
    AND DATE(ro_order_time) = '{$s_date}'
    GROUP BY ro_status
";
$count_res = sql_query($count_sql);
while ($row = sql_fetch_array($count_res)) {
    $status_counts[$row['ro_status']] = $row['cnt'];
}
?>

<h2 class="page_title">📋 주문 관리</h2>

<!-- 날짜 선택 -->
<div class="card">
    <form method="get" style="display: flex; gap: 10px; align-items: center;">
        <label style="font-weight: 600; color: #555;">날짜:</label>
        <input type="date" name="s_date" value="<?php echo $s_date; ?>" class="frm_input" style="width: auto;">
        <button type="submit" class="btn btn_primary btn_sm">조회</button>
        <a href="./order.php" class="btn btn_outline btn_sm">오늘</a>
    </form>
</div>

<!-- 상태별 필터 탭 -->
<div style="display: flex; gap: 8px; margin-bottom: 15px; overflow-x: auto;">
    <a href="./order.php?s_date=<?php echo $s_date; ?>" class="btn <?php echo $s_status == '' ? 'btn_primary' : 'btn_outline'; ?> btn_sm" style="white-space: nowrap;">
        전체 <?php echo array_sum($status_counts); ?>
    </a>
    <a href="./order.php?s_status=pending&s_date=<?php echo $s_date; ?>" class="btn <?php echo $s_status == 'pending' ? 'btn_primary' : 'btn_outline'; ?> btn_sm" style="white-space: nowrap;">
        <i class="fa fa-clock"></i> 접수대기 <?php echo $status_counts['pending']; ?>
    </a>
    <a href="./order.php?s_status=cooking&s_date=<?php echo $s_date; ?>" class="btn <?php echo $s_status == 'cooking' ? 'btn_primary' : 'btn_outline'; ?> btn_sm" style="white-space: nowrap;">
        <i class="fa fa-fire"></i> 조리중 <?php echo $status_counts['cooking']; ?>
    </a>
    <a href="./order.php?s_status=completed&s_date=<?php echo $s_date; ?>" class="btn <?php echo $s_status == 'completed' ? 'btn_primary' : 'btn_outline'; ?> btn_sm" style="white-space: nowrap;">
        <i class="fa fa-check"></i> 완료 <?php echo $status_counts['completed']; ?>
    </a>
    <a href="./order.php?s_status=cancelled&s_date=<?php echo $s_date; ?>" class="btn <?php echo $s_status == 'cancelled' ? 'btn_primary' : 'btn_outline'; ?> btn_sm" style="white-space: nowrap;">
        <i class="fa fa-times"></i> 취소 <?php echo $status_counts['cancelled']; ?>
    </a>
</div>

<!-- 주문 목록 -->
<?php
$order_count = 0;
while ($order = sql_fetch_array($result)) {
    $order_count++;

    $status_badge = '';
    $status_icon = '';
    if ($order['ro_status'] == 'pending') {
        $status_badge = 'status_received';
        $status_icon = 'fa-clock';
    } else if ($order['ro_status'] == 'cooking') {
        $status_badge = 'status_cooking';
        $status_icon = 'fa-fire';
    } else if ($order['ro_status'] == 'completed') {
        $status_badge = 'status_completed';
        $status_icon = 'fa-check';
    } else {
        $status_badge = 'status_cancelled';
        $status_icon = 'fa-times';
    }

    $status_text = '';
    if ($order['ro_status'] == 'pending') $status_text = '접수대기';
    else if ($order['ro_status'] == 'cooking') $status_text = '조리중';
    else if ($order['ro_status'] == 'completed') $status_text = '완료';
    else $status_text = '취소';
?>
<div class="card" id="order_<?php echo $order['ro_id']; ?>">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-weight: 600; font-size: 16px;">#<?php echo $order['ro_order_number']; ?></span>
            <span class="badge <?php echo $status_badge; ?>">
                <i class="fa <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
            </span>
        </div>
        <span style="font-size: 12px; color: #888;">
            <?php echo date('H:i', strtotime($order['ro_order_time'])); ?>
        </span>
    </div>

    <!-- 주문 메뉴 -->
    <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 10px;">
        <?php
        // 주문 상세 내역 조회
        $items = sql_query("
            SELECT roi.*, rm.rm_image
            FROM rain_restaurant_order_item roi
            LEFT JOIN rain_restaurant_menu rm ON roi.rm_id = rm.rm_id
            WHERE roi.ro_id = '{$order['ro_id']}'
        ");
        while ($item = sql_fetch_array($items)) {
        ?>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <?php if ($item['rm_image']) { ?>
            <img src="<?php echo G5_DATA_URL; ?>/menu/<?php echo $item['rm_image']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
            <?php } else { ?>
            <div style="width: 50px; height: 50px; background: #eee; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="fa fa-cutlery" style="color: #ccc;"></i>
            </div>
            <?php } ?>
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 3px;">
                    <?php echo get_text($item['roi_menu_name']); ?>
                </div>
                <div style="font-size: 12px; color: #888;">
                    <?php echo number_format($item['roi_price']); ?>원 x <?php echo $item['roi_quantity']; ?>
                </div>
            </div>
            <div style="font-weight: 600; color: #667eea;">
                <?php echo number_format($item['roi_subtotal']); ?>원
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- 고객 정보 -->
    <?php if ($order['ro_customer_name'] || $order['ro_customer_hp']) { ?>
    <div style="font-size: 13px; color: #555; margin-bottom: 10px;">
        <?php if ($order['ro_customer_name']) { ?>
        <i class="fa fa-user"></i> <?php echo get_text($order['ro_customer_name']); ?>
        <?php } ?>
        <?php if ($order['ro_customer_hp']) { ?>
        <span style="margin-left: 10px;">
            <i class="fa fa-phone"></i> <?php echo get_text($order['ro_customer_hp']); ?>
        </span>
        <?php } ?>
    </div>
    <?php } ?>

    <!-- 요청사항 -->
    <?php if ($order['ro_memo']) { ?>
    <div style="background: #fff3e0; padding: 10px; border-radius: 8px; font-size: 13px; color: #f57c00; margin-bottom: 10px;">
        <i class="fa fa-comment"></i> <?php echo get_text($order['ro_memo']); ?>
    </div>
    <?php } ?>

    <!-- 총 금액 -->
    <div style="text-align: right; font-size: 18px; font-weight: 700; color: #333; margin-bottom: 10px;">
        <i class="fa fa-won"></i> <?php echo number_format($order['ro_total_price']); ?>원
    </div>

    <!-- 상태 변경 버튼 -->
    <?php if ($order['ro_status'] == 'pending') { ?>
    <div style="display: flex; gap: 8px;">
        <button onclick="updateOrderStatus(<?php echo $order['ro_id']; ?>, 'cooking')" class="btn btn_warning btn_full">
            <i class="fa fa-fire"></i> 조리 시작
        </button>
        <button onclick="updateOrderStatus(<?php echo $order['ro_id']; ?>, 'cancelled')" class="btn btn_danger btn_full">
            <i class="fa fa-times"></i> 주문 취소
        </button>
    </div>
    <?php } else if ($order['ro_status'] == 'cooking') { ?>
    <div style="display: flex; gap: 8px;">
        <button onclick="updateOrderStatus(<?php echo $order['ro_id']; ?>, 'completed')" class="btn btn_success btn_full">
            <i class="fa fa-check"></i> 조리 완료
        </button>
        <button onclick="updateOrderStatus(<?php echo $order['ro_id']; ?>, 'cancelled')" class="btn btn_danger btn_full">
            <i class="fa fa-times"></i> 주문 취소
        </button>
    </div>
    <?php } else if ($order['ro_status'] == 'completed') { ?>
    <div style="text-align: center; padding: 10px; background: #e8f5e9; border-radius: 8px; color: #388e3c; font-weight: 600;">
        <i class="fa fa-check-circle"></i> 완료된 주문입니다
    </div>
    <?php } else { ?>
    <div style="text-align: center; padding: 10px; background: #ffebee; border-radius: 8px; color: #d32f2f; font-weight: 600;">
        <i class="fa fa-times-circle"></i> 취소된 주문입니다
    </div>
    <?php } ?>
</div>
<?php } ?>

<?php if ($order_count == 0) { ?>
<div class="empty_state">
    <i class="fa fa-receipt"></i>
    <p>주문 내역이 없습니다</p>
</div>
<?php } ?>

<script>
function updateOrderStatus(ro_id, status) {
    var statusText = '';
    if (status === 'cooking') statusText = '조리중';
    else if (status === 'completed') statusText = '완료';
    else if (status === 'cancelled') statusText = '취소';

    partnerConfirm('주문 상태를 [' + statusText + ']로 변경하시겠습니까?', function() {
        partnerAjax('./partner_ajax.php', {
            action: 'update_order_status',
            ro_id: ro_id,
            status: status
        }, function(res) {
            partnerAlert('주문 상태가 변경되었습니다.', 'success');
            setTimeout(function() {
                partnerReload();
            }, 500);
        });
    });
}

// 30초마다 자동 새로고침 (실시간 주문 반영)
setInterval(function() {
    // 사용자가 현재 페이지에 있을 때만 새로고침
    partnerReload();
}, 30000);
</script>

<?php
include_once('./partner_tail.php');
?>
