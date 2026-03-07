<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '음식점/상점 관리';
include_once('./admin.head.php');

$s_status = isset($_GET['s_status']) && is_array($_GET['s_status']) ? array_map('clean_xss_tags', $_GET['s_status']) : array();
$stx = isset($_GET['stx']) ? clean_xss_tags($_GET['stx']) : '';

$sql_search = " WHERE 1=1 ";

// [SaaS 핵심 격리]
if (defined('MY_FS_ID') && MY_FS_ID > 0) {
    $sql_search .= " AND r.fs_id = '" . MY_FS_ID . "' ";
}

if ($stx) {
    $sql_search .= " and r.rt_name like '%{$stx}%' ";
}

if (!empty($s_status)) {
    $status_str = implode("','", $s_status);
    $sql_search .= " and r.rt_status IN ('{$status_str}') ";
}

$sql_common = " from rain_restaurant_info r left join rain_festival f on r.fs_id = f.fs_id " . $sql_search;

$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'] ?? 0;

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select r.*, f.fs_name $sql_common order by r.rt_id desc limit $from_record, $rows ";
$result = sql_query($sql);

$qstr = '';
foreach ($s_status as $val) { $qstr .= "&amp;s_status[]=".urlencode($val); }
?>

<div class="local_desc01 local_desc">
    <p><strong>[상점 관리 목록 안내]</strong></p>
    <ul>
        <li>축제에 입점한 푸드트럭, 플리마켓 등의 정보를 관리합니다.</li>
        <li><strong>POS 연동 계정:</strong> 상점 주인이 자신의 스마트폰으로 쿠폰을 스캔하려면 연동된 아이디가 필수입니다.</li>
    </ul>
</div>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <div style="display:flex; gap:10px; align-items:center; border:1px solid #ccc; padding:0 10px; height:30px; border-radius:3px; background:#fff;">
        <strong style="color:#333;">상태</strong>
        <label><input type="checkbox" name="s_status[]" value="영업중" <?php echo in_array('영업중', $s_status) ? 'checked' : ''; ?>> 영업중</label>
        <label><input type="checkbox" name="s_status[]" value="준비중" <?php echo in_array('준비중', $s_status) ? 'checked' : ''; ?>> 준비중</label>
        <label><input type="checkbox" name="s_status[]" value="마감" <?php echo in_array('마감', $s_status) ? 'checked' : ''; ?>> 마감</label>
    </div>
    <div style="display:flex; gap:5px; align-items:center;">
        <label for="stx" class="sound_only">상점명 검색</label>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="상점명 검색" style="height:30px;">
        <button type="submit" class="btn_submit" title="검색">검색</button>
        <a href="./rain_restaurant_list.php" class="btn btn_02" style="height:30px; line-height:30px; border-radius:3px;">초기화</a>
    </div>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <?php if ($is_admin == 'super') { ?><th scope="col">소속 행사</th><?php } ?>
            <th scope="col">구분</th>
            <th scope="col">상점명</th>
            <th scope="col">위치/구역</th>
            <th scope="col">상태</th>
            <th scope="col">대표자</th>
            <th scope="col">POS 연동 계정(ID)</th>
            <th scope="col">앱 노출</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i%2);
            
            // 상태 라벨
            if($row['rt_status'] == '마감') $st_cls = 'background:#888; color:#fff; padding:3px 8px; border-radius:3px;';
            else if($row['rt_status'] == '영업중') $st_cls = 'background:#68d0a7; color:#fff; padding:3px 8px; border-radius:3px;';
            else $st_cls = 'background:#ffa700; color:#fff; padding:3px 8px; border-radius:3px;';
            
            $mb_display = $row['mb_id'] ? "<strong style='color:#3f51b5;'>{$row['mb_id']}</strong>" : "<span style='color:#ccc;'>미연동</span>";
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <?php if ($is_admin == 'super') { ?>
            <td><?php echo $row['fs_name'] ? get_text($row['fs_name']) : '<span style="color:#ccc;">미지정</span>'; ?></td>
            <?php } ?>
            <td><?php echo get_text($row['rt_type']); ?></td>
            <td class="td_left" style="font-weight:bold;"><?php echo get_text($row['rt_name']); ?></td>
            <td class="td_left"><?php echo get_text($row['rt_location']); ?></td>
            <td><span style="<?php echo $st_cls; ?>"><?php echo $row['rt_status']; ?></span></td>
            <td><?php echo get_text($row['rt_manager_name']); ?></td>
            <td><?php echo $mb_display; ?></td>
            <td><?php echo $row['rt_is_show'] ? '<span style="color:#2CC185;">●</span>' : '<span style="color:#ccc;">●</span>'; ?></td>
            <td class="td_mng td_mng_s">
                <a href="./rain_restaurant_form.php?w=u&amp;rt_id=<?php echo $row['rt_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">수정</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="'.($is_admin == 'super' ? '10' : '9').'" class="empty_table">등록된 상점이 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_restaurant_form.php" class="btn btn_01">+ 상점 등록</a>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>
<?php include_once('./admin.tail.php'); ?>