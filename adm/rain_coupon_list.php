<?php
$sub_menu = "800400"; // 쿠폰 관리 메뉴 코드
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '쿠폰 관리';
include_once('./admin.head.php');

$s_status = isset($_GET['s_status']) && is_array($_GET['s_status']) ? array_map('clean_xss_tags', $_GET['s_status']) : array();
$stx = isset($_GET['stx']) ? clean_xss_tags($_GET['stx']) : '';

$sql_search = " WHERE 1=1 ";

// [SaaS 핵심 격리]
if (defined('MY_FS_ID') && MY_FS_ID > 0) {
    $sql_search .= " AND c.fs_id = '" . MY_FS_ID . "' ";
}

if ($stx) {
    $sql_search .= " and c.cp_name like '%{$stx}%' ";
}

if (!empty($s_status)) {
    $status_str = implode("','", $s_status);
    $sql_search .= " and c.cp_status IN ('{$status_str}') ";
}

$sql_common = " from rain_coupon_info c left join rain_festival f on c.fs_id = f.fs_id " . $sql_search;

$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'] ?? 0;

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select c.*, f.fs_name $sql_common order by c.cp_id desc limit $from_record, $rows ";
$result = sql_query($sql);

$qstr = '';
foreach ($s_status as $val) { $qstr .= "&amp;s_status[]=".urlencode($val); }
?>

<div class="local_desc01 local_desc">
    <p><strong>[쿠폰 관리 목록 안내]</strong></p>
    <ul>
        <li><strong>다운/사용량:</strong> 관람객이 다운로드한 횟수와 실제 현장에서 사용한 횟수가 실시간으로 반영됩니다.</li>
    </ul>
</div>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <div style="display:flex; gap:10px; align-items:center; border:1px solid #ccc; padding:0 10px; height:30px; border-radius:3px; background:#fff;">
        <strong style="color:#333;">상태</strong>
        <label><input type="checkbox" name="s_status[]" value="대기" <?php echo in_array('대기', $s_status) ? 'checked' : ''; ?>> 대기</label>
        <label><input type="checkbox" name="s_status[]" value="발급중" <?php echo in_array('발급중', $s_status) ? 'checked' : ''; ?>> 발급중</label>
        <label><input type="checkbox" name="s_status[]" value="마감" <?php echo in_array('마감', $s_status) ? 'checked' : ''; ?>> 마감</label>
    </div>
    <div style="display:flex; gap:5px; align-items:center;">
        <label for="stx" class="sound_only">쿠폰명 검색</label>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="쿠폰명 검색" style="height:30px;">
        <button type="submit" class="btn_submit" title="검색">검색</button>
        <a href="./rain_coupon_list.php" class="btn btn_02" style="height:30px; line-height:30px; border-radius:3px;">초기화</a>
    </div>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <?php if ($is_admin == 'super') { ?><th scope="col">소속 행사</th><?php } ?>
            <th scope="col">쿠폰명 (혜택)</th>
            <th scope="col">유효 기간</th>
            <th scope="col">발급 제한</th>
            <th scope="col">다운 / 사용</th>
            <th scope="col">상태</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i%2);
            
            // 혜택 텍스트 가공
            $benefit = '';
            if($row['cp_type'] == '금액할인') $benefit = number_format($row['cp_amount']).'원 할인';
            else if($row['cp_type'] == '퍼센트할인') $benefit = $row['cp_amount'].'% 할인';
            else $benefit = '교환권';

            // 발급 제한 텍스트
            $limit_txt = $row['cp_use_limit'] > 0 ? number_format($row['cp_use_limit']).'장' : '무제한';

            // 상태 라벨
            if($row['cp_status'] == '마감') $st_cls = 'background:#888; color:#fff; padding:3px 8px; border-radius:3px;';
            else if($row['cp_status'] == '발급중') $st_cls = 'background:#68d0a7; color:#fff; padding:3px 8px; border-radius:3px;';
            else $st_cls = 'background:#ffa700; color:#fff; padding:3px 8px; border-radius:3px;';
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <?php if ($is_admin == 'super') { ?>
            <td><?php echo $row['fs_name'] ? get_text($row['fs_name']) : '<span style="color:#ccc;">미지정</span>'; ?></td>
            <?php } ?>
            <td class="td_left">
                <strong><?php echo get_text($row['cp_name']); ?></strong><br>
                <span style="color:#ff3061; font-size:0.9em;">[<?php echo $row['cp_type']; ?>] <?php echo $benefit; ?></span>
            </td>
            <td><?php echo $row['cp_start_date']; ?> ~ <?php echo $row['cp_end_date']; ?></td>
            <td><?php echo $limit_txt; ?></td>
            <td style="font-weight:bold;">
                <span style="color:#3f51b5;"><?php echo number_format($row['cp_download_count']); ?></span> / 
                <span style="color:#e91e63;"><?php echo number_format($row['cp_used_count']); ?></span>
            </td>
            <td><span style="<?php echo $st_cls; ?>"><?php echo $row['cp_status']; ?></span></td>
            <td class="td_mng td_mng_s">
                <a href="./rain_coupon_form.php?w=u&amp;cp_id=<?php echo $row['cp_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">수정</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="'.($is_admin == 'super' ? '8' : '7').'" class="empty_table">등록된 쿠폰이 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_coupon_form.php" class="btn btn_01">+ 쿠폰 등록</a>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>
<?php include_once('./admin.tail.php'); ?>