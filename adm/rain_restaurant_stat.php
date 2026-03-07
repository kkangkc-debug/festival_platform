<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$rt_id = isset($_GET['rt_id']) ? (int)$_GET['rt_id'] : 0;
if (!$rt_id) alert('잘못된 접근입니다.', './rain_restaurant_list.php');

$sql = " SELECT * FROM rain_restaurant_info WHERE rt_id = '{$rt_id}' ";
$rt = sql_fetch($sql);
if (!$rt['rt_id']) alert('존재하지 않는 상점입니다.', './rain_restaurant_list.php');
if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
    if ($rt['fs_id'] != MY_FS_ID) alert('접근 권한이 없는 상점입니다.', './rain_restaurant_list.php');
}

$g5['title'] = get_text($rt['rt_name']) . ' - 통계 현황';
include_once('./admin.head.php');

// [임시 데이터] 실제 운영 시 주문 테이블 데이터를 SUM/COUNT 하여 적용
$total_orders = sql_fetch(" SELECT count(*) as cnt FROM rain_restaurant_order WHERE rt_id = '{$rt_id}' AND od_status != '취소' ")['cnt'];
$total_sales = sql_fetch(" SELECT sum(od_total_amount) as amt FROM rain_restaurant_order WHERE rt_id = '{$rt_id}' AND od_status != '취소' ")['amt'];
$avg_wait_time = "15분"; // 이 값은 추후 접수~완료 시간 차이로 계산
?>

<style>
.rain_rt_tabs { border-bottom: 2px solid #ddd; margin-bottom: 20px; display: flex; gap: 5px; }
.rain_rt_tabs a { padding: 10px 30px; background: #f5f5f5; color: #555; text-decoration: none; border-radius: 8px 8px 0 0; font-weight: bold; border: 1px solid #ddd; border-bottom: none; }
.rain_rt_tabs a.active { background: #fff; color: #3f51b5; border-top: 2px solid #3f51b5; padding-bottom: 12px; margin-bottom: -2px; }

/* 대시보드 카드 스타일 */
.stat_grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
.stat_card { background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); }
.stat_card .title { font-size: 15px; color: #666; margin-bottom: 15px; font-weight: bold; }
.stat_card .number { font-size: 32px; font-weight: 900; color: #333; margin-bottom: 10px; }
.stat_card .trend { font-size: 13px; color: #2CC185; }

.chart_grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
.chart_box { background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 25px; min-height: 250px; }
.chart_box .title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
.placeholder_text { display:flex; align-items:center; justify-content:center; height:150px; color:#aaa; font-size:14px; background:#f9f9f9; border-radius:5px; }
</style>

<div class="rain_rt_tabs">
    <a href="./rain_restaurant_form.php?w=u&rt_id=<?php echo $rt_id; ?>">기본 정보</a>
    <a href="./rain_restaurant_menu.php?rt_id=<?php echo $rt_id; ?>">메뉴 관리</a>
    <a href="./rain_restaurant_order.php?rt_id=<?php echo $rt_id; ?>">주문 내역</a>
    <a href="./rain_restaurant_stat.php?rt_id=<?php echo $rt_id; ?>" class="active">통계</a>
</div>

<div class="stat_grid">
    <div class="stat_card">
        <div class="title">총 주문 건수</div>
        <div class="number"><?php echo number_format($total_orders); ?>건</div>
        <div class="trend">▲ 15% (전일 동시각 대비)</div>
    </div>
    <div class="stat_card">
        <div class="title">총 누적 매출</div>
        <div class="number"><?php echo number_format((int)$total_sales); ?>원</div>
        <div class="trend">▲ 18% (전일 동시각 대비)</div>
    </div>
    <div class="stat_card">
        <div class="title">평균 대기시간</div>
        <div class="number"><?php echo $avg_wait_time; ?></div>
        <div class="trend" style="color:#ff3061;">▼ 2분 (전일 동시각 대비)</div>
    </div>
</div>

<div class="chart_grid">
    <div class="chart_box">
        <div class="title">일별 주문 및 매출 추이</div>
        <div class="placeholder_text">차트 영역 (데이터 누적 시 활성화)</div>
    </div>
    <div class="chart_box">
        <div class="title">시간대별 주문 현황</div>
        <div class="placeholder_text">막대 그래프 영역</div>
    </div>
</div>

<div class="chart_grid">
    <div class="chart_box">
        <div class="title">메뉴별 판매 순위 TOP 5</div>
        <table style="width:100%; border-collapse:collapse; text-align:left;">
            <tr style="border-bottom:1px solid #eee;">
                <th style="padding:10px 0;">순위</th>
                <th>메뉴명</th>
                <th style="text-align:right;">판매 건수</th>
                <th style="text-align:right;">매출액</th>
            </tr>
            <tr>
                <td style="padding:10px 0; font-weight:bold; color:#ff3061;">1</td>
                <td>떡볶이</td>
                <td style="text-align:right;">80건</td>
                <td style="text-align:right;">640,000원</td>
            </tr>
            <tr>
                <td style="padding:10px 0; font-weight:bold;">2</td>
                <td>어묵</td>
                <td style="text-align:right;">30건</td>
                <td style="text-align:right;">300,000원</td>
            </tr>
            <tr>
                <td style="padding:10px 0; font-weight:bold;">3</td>
                <td>튀김 모둠</td>
                <td style="text-align:right;">10건</td>
                <td style="text-align:right;">120,000원</td>
            </tr>
        </table>
    </div>
    <div class="chart_box">
        <div class="title">결제 수단 및 판매 비중</div>
        <div style="display:flex; gap:10px; height:150px;">
            <div class="placeholder_text" style="flex:1;">원형 차트 (카드/간편결제)</div>
            <div class="placeholder_text" style="flex:1;">원형 차트 (메뉴 비중)</div>
        </div>
    </div>
</div>

<?php include_once('./admin.tail.php'); ?>