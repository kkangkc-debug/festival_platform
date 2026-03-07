<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$rt_id = isset($_GET['rt_id']) ? (int)$_GET['rt_id'] : 0;
if (!$rt_id) alert('잘못된 접근입니다.', './rain_restaurant_list.php');

// 상점 기본 정보 가져오기 & SaaS 권한 체크
$sql = " SELECT * FROM rain_restaurant_info WHERE rt_id = '{$rt_id}' ";
$rt = sql_fetch($sql);
if (!$rt['rt_id']) alert('존재하지 않는 상점입니다.', './rain_restaurant_list.php');
if ($is_admin != 'super' && defined('MY_FS_ID') && MY_FS_ID > 0) {
    if ($rt['fs_id'] != MY_FS_ID) alert('접근 권한이 없는 상점입니다.', './rain_restaurant_list.php');
}

$g5['title'] = get_text($rt['rt_name']) . ' - 주문 내역';
include_once('./admin.head.php');

// 검색 및 필터
$s_status = isset($_GET['s_status']) ? clean_xss_tags($_GET['s_status']) : '';
$sql_search = " WHERE rt_id = '{$rt_id}' ";
if ($s_status) $sql_search .= " AND od_status = '{$s_status}' ";

// 상태별 카운트 요약
$cnt_wait = sql_fetch(" SELECT count(*) as cnt FROM rain_restaurant_order WHERE rt_id = '{$rt_id}' AND od_status = '대기중' ")['cnt'];
$cnt_ready = sql_fetch(" SELECT count(*) as cnt FROM rain_restaurant_order WHERE rt_id = '{$rt_id}' AND od_status = '준비중' ")['cnt'];
$cnt_done = sql_fetch(" SELECT count(*) as cnt FROM rain_restaurant_order WHERE rt_id = '{$rt_id}' AND od_status = '완료' ")['cnt'];
$cnt_total = sql_fetch(" SELECT count(*) as cnt FROM rain_restaurant_order WHERE rt_id = '{$rt_id}' ")['cnt'];

// 페이징 및 목록 호출
$sql = " SELECT count(*) as cnt FROM rain_restaurant_order " . $sql_search;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$from_record = ($page - 1) * $rows;

$sql = " SELECT * FROM rain_restaurant_order $sql_search ORDER BY od_id DESC LIMIT $from_record, $rows ";
$result = sql_query($sql);
?>

<style>
.rain_rt_tabs { border-bottom: 2px solid #ddd; margin-bottom: 20px; display: flex; gap: 5px; }
.rain_rt_tabs a { padding: 10px 30px; background: #f5f5f5; color: #555; text-decoration: none; border-radius: 8px 8px 0 0; font-weight: bold; border: 1px solid #ddd; border-bottom: none; }
.rain_rt_tabs a.active { background: #fff; color: #3f51b5; border-top: 2px solid #3f51b5; padding-bottom: 12px; margin-bottom: -2px; }

.summary_box_wrap { display: flex; gap: 15px; margin-bottom: 20px; }
.summary_box { flex: 1; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; }
.summary_box.total { background: #3f51b5; color: #fff; border-color: #3f51b5; }
.summary_title { font-size: 14px; margin-bottom: 10px; color: #888; }
.summary_box.total .summary_title { color: #ddd; }
.summary_num { font-size: 24px; font-weight: bold; color: #333; }
.summary_box.total .summary_num { color: #fff; }
</style>

<div class="rain_rt_tabs">
    <a href="./rain_restaurant_form.php?w=u&rt_id=<?php echo $rt_id; ?>">기본 정보</a>
    <a href="./rain_restaurant_menu.php?rt_id=<?php echo $rt_id; ?>">메뉴 관리</a>
    <a href="./rain_restaurant_order.php?rt_id=<?php echo $rt_id; ?>" class="active">주문 내역</a>
    <a href="./rain_restaurant_stat.php?rt_id=<?php echo $rt_id; ?>">통계</a>
</div>

<div class="summary_box_wrap">
    <div class="summary_box total">
        <div class="summary_title">전체 주문</div>
        <div class="summary_num"><?php echo number_format($cnt_total); ?>건</div>
    </div>
    <div class="summary_box">
        <div class="summary_title">대기중</div>
        <div class="summary_num" style="color:#ff3061;"><?php echo number_format($cnt_wait); ?>건</div>
    </div>
    <div class="summary_box">
        <div class="summary_title">준비중</div>
        <div class="summary_num" style="color:#ffa700;"><?php echo number_format($cnt_ready); ?>건</div>
    </div>
    <div class="summary_box">
        <div class="summary_title">완료</div>
        <div class="summary_num" style="color:#2CC185;"><?php echo number_format($cnt_done); ?>건</div>
    </div>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get" style="display:flex; gap:10px; align-items:center;">
    <input type="hidden" name="rt_id" value="<?php echo $rt_id; ?>">
    <select name="s_status" class="frm_input" style="height:30px;">
        <option value="">-- 상태 전체 --</option>
        <option value="대기중" <?php echo $s_status=='대기중'?'selected':''; ?>>대기중</option>
        <option value="준비중" <?php echo $s_status=='준비중'?'selected':''; ?>>준비중</option>
        <option value="완료" <?php echo $s_status=='완료'?'selected':''; ?>>완료</option>
    </select>
    <button type="submit" class="btn_submit">검색</button>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <th scope="col">주문번호</th>
            <th scope="col">주문시간</th>
            <th scope="col">주문 내역(요약)</th>
            <th scope="col">금액</th>
            <th scope="col">결제수단</th>
            <th scope="col">상태</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i%2);
            
            // 상태 라벨
            $st_cls = "color:#333;";
            if ($row['od_status'] == '대기중') $st_cls = "color:#ff3061; font-weight:bold;";
            else if ($row['od_status'] == '준비중') $st_cls = "color:#ffa700; font-weight:bold;";
            else if ($row['od_status'] == '완료') $st_cls = "color:#2CC185; font-weight:bold;";

            // 임시 요약 텍스트 (실제로는 상세 테이블 조인 필요)
            $order_summary = "조회 필요 (상세 연동 전)";
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <td style="font-weight:bold;"><?php echo $row['od_no']; ?></td>
            <td><?php echo substr($row['od_datetime'], 0, 16); ?></td>
            <td class="td_left"><?php echo $order_summary; ?></td>
            <td style="font-weight:bold;"><?php echo number_format($row['od_total_amount']); ?>원</td>
            <td><?php echo $row['od_pay_method']; ?></td>
            <td><span style="<?php echo $st_cls; ?>"><?php echo $row['od_status']; ?></span></td>
            <td class="td_mng">
                <a href="#" onclick="alert('주문 상세 보기 준비중'); return false;" class="btn btn_03">상세</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="8" class="empty_table">등록된 주문 내역이 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?rt_id='.$rt_id.'&amp;s_status='.$s_status.'&amp;page='); ?>

<?php include_once('./admin.tail.php'); ?>