<?php
$sub_menu = "800200";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '체크인존 관리';
include_once('./admin.head.php');

// 검색 조건 조립
$sql_search = " WHERE 1=1 ";
if (isset($_GET['s_sync']) && $_GET['s_sync']) {
    $sql_search .= " and ci_sync_status = '{$_GET['s_sync']}' ";
}
if (isset($_GET['s_status']) && $_GET['s_status']) {
    $sql_search .= " and ci_status = '{$_GET['s_status']}' ";
}
if (isset($_GET['stx']) && $_GET['stx']) {
    $sql_search .= " and ci_name like '%{$_GET['stx']}%' ";
}

$sql_common = " from rain_checkin_info " . $sql_search;

$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select * $sql_common order by ci_id desc limit $from_record, $rows ";
$result = sql_query($sql);
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
    <select name="s_sync" id="s_sync">
        <option value="">연동 상태 전체</option>
        <option value="정상" <?php echo get_selected($_GET['s_sync'] ?? '', '정상'); ?>>정상</option>
        <option value="장애" <?php echo get_selected($_GET['s_sync'] ?? '', '장애'); ?>>장애</option>
        <option value="오프라인" <?php echo get_selected($_GET['s_sync'] ?? '', '오프라인'); ?>>오프라인</option>
    </select>
    <select name="s_status" id="s_status">
        <option value="">운영 설정 전체</option>
        <option value="운영" <?php echo get_selected($_GET['s_status'] ?? '', '운영'); ?>>운영</option>
        <option value="장애" <?php echo get_selected($_GET['s_status'] ?? '', '장애'); ?>>장애</option>
        <option value="마감" <?php echo get_selected($_GET['s_status'] ?? '', '마감'); ?>>마감</option>
    </select>
    <label for="stx" class="sound_only">체크인존 명 검색</label>
    <input type="text" name="stx" value="<?php echo get_text($_GET['stx'] ?? ''); ?>" id="stx" class="frm_input" placeholder="체크인존 명 검색">
    <button type="submit" class="btn_submit">검색</button>
    <a href="./checkin_list.php" class="btn btn_02">초기화</a>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <th scope="col">체크인존 명</th>
            <th scope="col">위치</th>
            <th scope="col">연동상태</th>
            <th scope="col">당일 입장객 수</th>
            <th scope="col">운영 설정</th>
            <th scope="col">담당자명</th>
            <th scope="col">사용자 노출</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i%2);
            // 연동 상태 색상 클래스 (예시)
            $sync_cls = '';
            if($row['ci_sync_status'] == '장애') $sync_cls = 'color_st01';
            else if($row['ci_sync_status'] == '정상') $sync_cls = 'color_st02';
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <td class="td_left"><?php echo get_text($row['ci_name']); ?></td>
            <td class="td_left"><?php echo get_text($row['ci_location']); ?></td>
            <td><span class="<?php echo $sync_cls; ?>"><?php echo $row['ci_sync_status']; ?></span></td>
            <td><?php echo number_format($row['ci_today_visitors']); ?></td>
            <td><?php echo $row['ci_status']; ?></td>
            <td><?php echo get_text($row['ci_manager_name']); ?></td>
            <td><?php echo $row['ci_is_show'] ? '노출' : '미노출'; ?></td>
            <td class="td_mng td_mng_s">
                <a href="./checkin_view.php?ci_id=<?php echo $row['ci_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">상세</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="9" class="empty_table">자료가 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./checkin_form.php" class="btn btn_01">+ 체크인존 등록</a>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<?php include_once('./admin.tail.php'); ?>