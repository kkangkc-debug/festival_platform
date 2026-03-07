<?php
$sub_menu = "900100";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '행사 개설/목록 관리';
include_once('./admin.head.php');

$sql_common = " from rain_festival ";
$sql_search = " where 1=1 ";

// 검색 조건 (필요시 추가)
$stx = isset($_GET['stx']) ? clean_xss_tags($_GET['stx']) : '';
if ($stx) {
    $sql_search .= " and fs_name like '%{$stx}%' ";
}

$sql_common .= $sql_search;

// 페이징
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select * $sql_common order by fs_id desc limit $from_record, $rows ";
$result = sql_query($sql);
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 행사 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<label for="stx" class="sound_only">행사명 검색</label>
<input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="행사명 검색">
<button type="submit" class="btn_submit">검색</button>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col">ID</th>
        <th scope="col">행사명</th>
        <th scope="col">기간</th>
        <th scope="col">상태</th>
        <th scope="col">등록일</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        $bg = 'bg'.($i%2);
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_id"><?php echo $row['fs_id']; ?></td>
        <td class="td_left"><?php echo get_text($row['fs_name']); ?></td>
        <td class="td_date"><?php echo $row['fs_start_date']; ?> ~ <?php echo $row['fs_end_date']; ?></td>
        <td class="td_mng"><?php echo $row['fs_status']; ?></td>
        <td class="td_date"><?php echo substr($row['fs_datetime'], 0, 10); ?></td>
        <td class="td_mng td_mng_s">
            <a href="./festival_form.php?w=u&amp;fs_id=<?php echo $row['fs_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">수정</a>
        </td>
    </tr>
    <?php } if ($i == 0) { echo '<tr><td colspan="6" class="empty_table">개설된 행사가 없습니다.</td></tr>'; } ?>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./festival_form.php" class="btn btn_01">+ 새 행사 개설</a>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<?php include_once('./admin.tail.php'); ?>