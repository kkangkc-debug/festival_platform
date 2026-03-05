<?php
$sub_menu = "800100";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '주차장 관리';
include_once('./admin.head.php');

$sql_search = " WHERE 1=1 ";
if ($stx) {
    $sql_search .= " and pi_name like '%$stx%' ";
}
// 추가 필터(주차유형, 혼잡도)는 $sql_search 에 덧붙이면 됩니다.

$sql_common = " from rain_park_info ";
$sql_common .= $sql_search;

// 페이징
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select * $sql_common order by pi_id desc limit $from_record, $rows ";
$result = sql_query($sql);
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 주차장 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
    <label for="stx" class="sound_only">주차장 명 검색</label>
    <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="주차장 명 검색">
    <button type="submit" class="btn_submit">검색</button>
    <a href="./parking_list.php" class="btn btn_02">초기화</a>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <th scope="col">주차장 명</th>
            <th scope="col">위치</th>
            <th scope="col">주차 유형</th>
            <th scope="col">주차현황(잔여/총)</th>
            <th scope="col">혼잡도</th>
            <th scope="col">담당자명</th>
            <th scope="col">노출</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $types = array();
            if($row['pi_type_general']) $types[] = '일반';
            if($row['pi_type_barrier']) $types[] = '베리어프리';
            if($row['pi_type_large']) $types[] = '대형';
            
            // 용량 계산
            $total_capa = $row['pi_capa_general'] + $row['pi_capa_pregnant'] + $row['pi_capa_compact'] + $row['pi_capa_eco'] + $row['pi_capa_large'];
            $remain = max(0, $total_capa - $row['pi_current_parked']);
            $rate = ($total_capa > 0) ? ($remain / $total_capa) * 100 : 0;
            
            // 혼잡도 라벨 (임의 클래스 적용)
            if($remain == 0) { $cong_txt = '만차'; $cong_cls = 'color_st06'; }
            else if($rate < 10) { $cong_txt = '혼잡'; $cong_cls = 'color_st01'; } // 빨강
            else if($rate < 50) { $cong_txt = '보통'; $cong_cls = 'color_st04'; } // 노랑
            else { $cong_txt = '여유'; $cong_cls = 'color_st02'; } // 초록

            $bg = 'bg'.($i%2);
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <td class="td_left"><?php echo get_text($row['pi_name']); ?></td>
            <td class="td_left"><?php echo get_text($row['pi_location']); ?></td>
            <td><?php echo implode(', ', $types); ?></td>
            <td><?php echo number_format($remain).' / '.number_format($total_capa); ?></td>
            <td><span class="<?php echo $cong_cls; ?>"><?php echo $cong_txt; ?></span></td>
            <td><?php echo get_text($row['pi_manager_name']); ?></td>
            <td><?php echo $row['pi_is_show'] ? '노출' : '미노출'; ?></td>
            <td class="td_mng td_mng_s">
                <a href="./parking_view.php?pi_id=<?php echo $row['pi_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">상세</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="9" class="empty_table">자료가 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./parking_form.php" class="btn btn_01">+ 주차장 등록</a>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<?php include_once('./admin.tail.php'); ?>