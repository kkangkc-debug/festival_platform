<?php
// [수정] define('_GNUBOARD_', true); 코드를 삭제했습니다. (common.php가 알아서 처리합니다)ㅁㅁ
include_once('./_common.php');

// 테이블 자동 생성 (없을 경우)
partner_create_tables();

$g5['title'] = '상점 관리 홈';
include_once('./partner_head.php');

// 오늘 날짜
$today = date('Y-m-d');
$rt_id = $partner_shop['rt_id'];

// 통계 데이터 조회
// 1. 오늘 주문 수
$today_orders = sql_fetch("
    SELECT COUNT(*) as cnt, SUM(ro_total_price) as total
    FROM rain_restaurant_order
    WHERE rt_id = '{$rt_id}'
    AND DATE(ro_order_time) = '{$today}'
    AND ro_status IN ('pending', 'cooking', 'completed')
");
$today_order_count = $today_orders['cnt'] ?? 0;
$today_sales = $today_orders['total'] ?? 0;

// 2. 주문 상태별 카운트
$pending_count = sql_fetch("
    SELECT COUNT(*) as cnt
    FROM rain_restaurant_order
    WHERE rt_id = '{$rt_id}'
    AND ro_status = 'pending'
    AND DATE(ro_order_time) = '{$today}'
")['cnt'] ?? 0;

$cooking_count = sql_fetch("
    SELECT COUNT(*) as cnt
    FROM rain_restaurant_order
    WHERE rt_id = '{$rt_id}'
    AND ro_status = 'cooking'
    AND DATE(ro_order_time) = '{$today}'
")['cnt'] ?? 0;

$completed_count = sql_fetch("
    SELECT COUNT(*) as cnt
    FROM rain_restaurant_order
    WHERE rt_id = '{$rt_id}'
    AND ro_status = 'completed'
    AND DATE(ro_order_time) = '{$today}'
")['cnt'] ?? 0;

// 3. 메뉴 수
$menu_count = sql_fetch("
    SELECT COUNT(*) as cnt
    FROM rain_restaurant_menu
    WHERE rt_id = '{$rt_id}'
    AND rm_is_active = 1
")['cnt'] ?? 0;

// 4. 최근 주문 5건
$recent_orders = sql_query("
    SELECT ro.*, GROUP_CONCAT(rm.rm_name SEPARATOR ', ') as menu_names
    FROM rain_restaurant_order ro
    LEFT JOIN rain_restaurant_order_item roi ON ro.ro_id = roi.ro_id
    LEFT JOIN rain_restaurant_menu rm ON roi.rm_id = rm.rm_id
    WHERE ro.rt_id = '{$rt_id}'
    GROUP BY ro.ro_id
    ORDER BY ro.ro_order_time DESC
    LIMIT 5
");
?>

<h2 class="page_title">📊 오늘의 현황</h2>

<div class="card">
    <div class="card_title">오늘의 매출</div>
    <div class="stat_card">
        <div>
            <div class="stat_value"><?php echo number_format($today_sales); ?>원</div>
            <div class="stat_label">총 <?php echo number_format($today_order_count); ?>건 주문</div>
        </div>
        <div class="stat_icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
            <i class="fa fa-won"></i>
        </div>
    </div>
</div>

<div style="display: flex; gap: 10px; margin-bottom: 15px;">
    <div class="card" style="flex: 1; text-align: center;">
        <div class="stat_value" style="color: #1976d2; font-size: 28px;"><?php echo number_format($pending_count); ?></div>
        <div class="stat_label">접수 대기</div>
    </div>
    <div class="card" style="flex: 1; text-align: center;">
        <div class="stat_value" style="color: #f57c00; font-size: 28px;"><?php echo number_format($cooking_count); ?></div>
        <div class="stat_label">조리중</div>
    </div>
    <div class="card" style="flex: 1; text-align: center;">
        <div class="stat_value" style="color: #388e3c; font-size: 28px;"><?php echo number_format($completed_count); ?></div>
        <div class="stat_label">완료</div>
    </div>
</div>

<?php if ($pending_count > 0) { ?>
    <div class="alert alert_warning">
        <i class="fa fa-bell"></i> <strong>접수 대기 중인 주문이 <?php echo number_format($pending_count); ?>건 있습니다!</strong>
        <a href="<?php echo G5_PARTNER_URL; ?>/order.php" class="btn btn_primary btn_sm" style="margin-left: 10px;">바로가기</a>
    </div>
<?php } ?>

<div class="card">
    <div class="card_title">⚡ 빠른 기능</div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <a href="<?php echo G5_PARTNER_URL; ?>/order.php" class="btn btn_primary">
            <i class="fa fa-list"></i> 주문 관리
        </a>
        <a href="<?php echo G5_PARTNER_URL; ?>/menu.php" class="btn btn_success">
            <i class="fa fa-cutlery"></i> 메뉴 관리
        </a>
        <a href="<?php echo G5_PARTNER_URL; ?>/sales.php" class="btn btn_warning">
            <i class="fa fa-bar-chart"></i> 매출 통계
        </a>
        <button onclick="partnerToggleShopStatus()" class="btn btn_outline">
            <i class="fa fa-power-off"></i> 영업상태 변경
        </button>
    </div>
</div>

<div class="card">
    <div class="card_title">🏪 내 상점 정보</div>
    <table style="width: 100%; font-size: 13px;">
        <tr>
            <td style="padding: 8px 0; color: #888;">상점명</td>
            <td style="padding: 8px 0; font-weight: 600;"><?php echo get_text($partner_shop['rt_name']); ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #888;">구분</td>
            <td style="padding: 8px 0;"><?php echo get_text($partner_shop['rt_type']); ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #888;">위치</td>
            <td style="padding: 8px 0;"><?php echo get_text($partner_shop['rt_location']); ?></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #888;">영업상태</td>
            <td style="padding: 8px 0;">
                <?php if ($partner_shop['rt_status'] == '영업중') { ?>
                    <span class="badge badge_success">영업중</span>
                <?php } else if ($partner_shop['rt_status'] == '준비중') { ?>
                    <span class="badge badge_warning">준비중</span>
                <?php } else { ?>
                    <span class="badge badge_danger">마감</span>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #888;">앱 노출</td>
            <td style="padding: 8px 0;">
                <?php echo $partner_shop['rt_is_show'] ? '<span style="color: #2CC185;">● 노출중</span>' : '<span style="color: #ccc;">● 미노출</span>'; ?>
            </td>
        </tr>
    </table>
</div>

<div class="card">
    <div class="card_title" style="display: flex; justify-content: space-between; align-items: center;">
        <span>📋 최근 주문</span>
        <a href="<?php echo G5_PARTNER_URL; ?>/order.php" style="font-size: 12px; color: #667eea;">더보기</a>
    </div>
    <?php
    $has_recent = false;
    while ($order = sql_fetch_array($recent_orders)) {
        $has_recent = true;
        $status_badge = '';
        if ($order['ro_status'] == 'pending') $status_badge = 'status_received';
        else if ($order['ro_status'] == 'cooking') $status_badge = 'status_cooking';
        else if ($order['ro_status'] == 'completed') $status_badge = 'status_completed';
        else $status_badge = 'status_cancelled';

        $status_text = '';
        if ($order['ro_status'] == 'pending') $status_text = '접수대기';
        else if ($order['ro_status'] == 'cooking') $status_text = '조리중';
        else if ($order['ro_status'] == 'completed') $status_text = '완료';
        else $status_text = '취소';
    ?>
    <div style="border-bottom: 1px solid #eee; padding: 12px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <span style="font-weight: 600;">#<?php echo $order['ro_id']; ?></span>
            <span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
        </div>
        <div style="font-size: 13px; color: #555; margin-bottom: 3px;">
            <?php echo get_text($order['menu_names']); ?>
        </div>
        <div style="font-size: 12px; color: #888;">
            <i class="fa fa-clock"></i> <?php echo substr($order['ro_order_time'], 5, 11); ?>
            <span style="margin-left: 10px;">
                <i class="fa fa-won"></i> <?php echo number_format($order['ro_total_price']); ?>원
            </span>
        </div>
    </div>
    <?php } ?>

    <?php if (!$has_recent) { ?>
    <div class="empty_state">
        <i class="fa fa-receipt"></i>
        <p>아직 주문이 없습니다</p>
    </div>
    <?php } ?>
</div>

<div class="card">
    <div class="card_title" style="display: flex; justify-content: space-between; align-items: center;">
        <span>🍽️ 메뉴 관리</span>
        <a href="<?php echo G5_PARTNER_URL; ?>/menu.php" style="font-size: 12px; color: #667eea;">더보기</a>
    </div>
    <div style="text-align: center; padding: 10px 0;">
        <div style="font-size: 32px; font-weight: 700; color: #667eea; margin-bottom: 5px;">
            <?php echo number_format($menu_count); ?>
        </div>
        <div style="font-size: 13px; color: #888;">등록된 메뉴 수</div>
    </div>
</div>

<script>
// 영업상태 토글
function partnerToggleShopStatus() {
    partnerConfirm('영업상태를 변경하시겠습니까?', function() {
        partnerAjax('./partner_ajax.php', {
            action: 'toggle_shop_status',
            rt_id: '<?php echo $partner_shop['rt_id']; ?>',
            current_status: '<?php echo $partner_shop['rt_status']; ?>'
        }, function(res) {
            partnerAlert('영업상태가 변경되었습니다.', 'success');
            setTimeout(function() {
                partnerReload();
            }, 1000);
        });
    });
}
</script>

<?php
include_once('./partner_tail.php');

// 테이블 자동 생성 함수
function partner_create_tables() {
    global $g5;

    // rain_restaurant_menu 테이블 생성
    sql_query("
        CREATE TABLE IF NOT EXISTS `rain_restaurant_menu` (
            `rm_id` int(11) NOT NULL AUTO_INCREMENT,
            `rt_id` int(11) NOT NULL COMMENT '상점 ID (rain_restaurant_info.rt_id)',
            `fs_id` int(11) NOT NULL DEFAULT 0 COMMENT '행사 ID (SaaS 격리용)',
            `rm_name` varchar(200) NOT NULL COMMENT '메뉴명',
            `rm_description` text COMMENT '메뉴 설명',
            `rm_price` int(11) NOT NULL DEFAULT 0 COMMENT '가격',
            `rm_image` varchar(255) COMMENT '메뉴 이미지',
            `rm_category` varchar(100) COMMENT '메뉴 카테고리',
            `rm_sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '정렬 순서',
            `rm_is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부 (0:비활성, 1:활성)',
            `rm_sold_out` tinyint(1) NOT NULL DEFAULT 0 COMMENT '품절 여부 (0:판매중, 1:품절)',
            `rm_reg_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
            `rm_update_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
            PRIMARY KEY (`rm_id`),
            KEY `rt_id` (`rt_id`),
            KEY `fs_id` (`fs_id`),
            KEY `rm_is_active` (`rm_is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='상점 메뉴 정보'
    ", true);

    // rain_restaurant_order 테이블 생성
    sql_query("
        CREATE TABLE IF NOT EXISTS `rain_restaurant_order` (
            `ro_id` int(11) NOT NULL AUTO_INCREMENT,
            `rt_id` int(11) NOT NULL COMMENT '상점 ID',
            `fs_id` int(11) NOT NULL DEFAULT 0 COMMENT '행사 ID (SaaS 격리용)',
            `ro_order_number` varchar(50) NOT NULL COMMENT '주문번호',
            `ro_customer_name` varchar(100) COMMENT '고객명',
            `ro_customer_hp` varchar(20) COMMENT '고객 연락처',
            `ro_total_price` int(11) NOT NULL DEFAULT 0 COMMENT '총 주문 금액',
            `ro_status` enum('pending','cooking','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '주문 상태',
            `ro_order_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '주문 시간',
            `ro_complete_time` datetime DEFAULT NULL COMMENT '완료 시간',
            `ro_memo` text COMMENT '요청사항',
            PRIMARY KEY (`ro_id`),
            UNIQUE KEY `ro_order_number` (`ro_order_number`),
            KEY `rt_id` (`rt_id`),
            KEY `fs_id` (`fs_id`),
            KEY `ro_status` (`ro_status`),
            KEY `ro_order_time` (`ro_order_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='상점 주문 정보'
    ", true);

    // rain_restaurant_order_item 테이블 생성
    sql_query("
        CREATE TABLE IF NOT EXISTS `rain_restaurant_order_item` (
            `roi_id` int(11) NOT NULL AUTO_INCREMENT,
            `ro_id` int(11) NOT NULL COMMENT '주문 ID (rain_restaurant_order.ro_id)',
            `rt_id` int(11) NOT NULL COMMENT '상점 ID',
            `rm_id` int(11) NOT NULL COMMENT '메뉴 ID (rain_restaurant_menu.rm_id)',
            `roi_menu_name` varchar(200) NOT NULL COMMENT '메뉴명 (주문 당시 기준)',
            `roi_price` int(11) NOT NULL DEFAULT 0 COMMENT '단가',
            `roi_quantity` int(11) NOT NULL DEFAULT 1 COMMENT '수량',
            `roi_subtotal` int(11) NOT NULL DEFAULT 0 COMMENT '소계 (단가 x 수량)',
            PRIMARY KEY (`roi_id`),
            KEY `ro_id` (`ro_id`),
            KEY `rt_id` (`rt_id`),
            KEY `rm_id` (`rm_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='상점 주문 상세 정보'
    ", true);
}
?>