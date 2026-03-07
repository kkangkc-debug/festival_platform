<?php
$sub_menu = "800300"; // 체험부스 관리 메뉴 코드
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '체험부스 관리';
include_once('./admin.head.php');

// 필터 파라미터 초기화
$s_status = isset($_GET['s_status']) && is_array($_GET['s_status']) ? array_map('clean_xss_tags', $_GET['s_status']) : array();
$stx = isset($_GET['stx']) ? clean_xss_tags($_GET['stx']) : '';

$sql_search = " WHERE 1=1 ";

// =======================================================
// [SaaS 핵심 격리 로직] MY_FS_ID 기반 필터링
// =======================================================
if (defined('MY_FS_ID') && MY_FS_ID > 0) {
    $sql_search .= " AND b.fs_id = '" . MY_FS_ID . "' ";
}

if ($stx) {
    $sql_search .= " and b.bt_name like '%{$stx}%' ";
}

if (!empty($s_status)) {
    $status_str = implode("','", $s_status);
    $sql_search .= " and b.bt_status IN ('{$status_str}') ";
}

// rain_festival 테이블과 JOIN 하여 행사명 가져오기
$sql_common = " from rain_booth_info b left join rain_festival f on b.fs_id = f.fs_id " . $sql_search;

$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'] ?? 0;

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select b.*, f.fs_name $sql_common order by b.bt_id desc limit $from_record, $rows ";
$result = sql_query($sql);

$qstr = '';
foreach ($s_status as $val) { $qstr .= "&amp;s_status[]=".urlencode($val); }
?>

<div class="local_desc01 local_desc">
    <p><strong>[체험부스 관리 목록 안내]</strong></p>
    <ul>
        <li><strong>권한:</strong> 소속된 행사의 체험부스만 노출되며 관리할 수 있습니다.</li>
        <li><strong>상태:</strong> 운영(초록) / 점검(노랑) / 마감(회색)으로 현장 상태를 직관적으로 파악할 수 있습니다.</li>
        <li><strong>URL 복사:</strong> 버튼을 눌러 해당 체험부스의 모바일 관리(POS) 화면 주소를 담당자에게 전달하세요.</li>
    </ul>
</div>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <div style="display:flex; gap:10px; align-items:center; border:1px solid #ccc; padding:0 10px; height:30px; border-radius:3px; background:#fff;">
        <strong style="color:#333;">운영 설정</strong>
        <label><input type="checkbox" name="s_status[]" value="운영" <?php echo in_array('운영', $s_status) ? 'checked' : ''; ?>> 운영</label>
        <label><input type="checkbox" name="s_status[]" value="점검" <?php echo in_array('점검', $s_status) ? 'checked' : ''; ?>> 점검</label>
        <label><input type="checkbox" name="s_status[]" value="마감" <?php echo in_array('마감', $s_status) ? 'checked' : ''; ?>> 마감</label>
    </div>
    <div style="display:flex; gap:5px; align-items:center;">
        <label for="stx" class="sound_only">부스명 검색</label>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="부스명 검색" style="height:30px;">
        <button type="submit" class="btn_submit" title="검색">검색</button>
        <a href="./rain_booth_list.php" class="btn btn_02" style="height:30px; line-height:30px; border-radius:3px;">초기화</a>
    </div>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <?php if ($is_admin == 'super') { ?><th scope="col">소속 행사</th><?php } ?>
            <th scope="col">부스명</th>
            <th scope="col">위치(구역)</th>
            <th scope="col">운영 상태</th>
            <th scope="col">담당자명</th>
            <th scope="col">연락처</th>
            <th scope="col">앱 노출</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i%2);
            
            // 상태 라벨 디자인
            if($row['bt_status'] == '마감') {
                $status_cls = 'background:#888; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;';
            } else if($row['bt_status'] == '점검') {
                $status_cls = 'background:#ffa700; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;';
            } else {
                $status_cls = 'background:#68d0a7; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;';
            }
            
            // [추가] 부스 전용 POS URL 세팅
            $pos_url = G5_ADMIN_URL.'/rain_booth_staff_pos.php?bt_id='.$row['bt_id'];
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <?php if ($is_admin == 'super') { ?>
            <td><?php echo $row['fs_name'] ? get_text($row['fs_name']) : '<span style="color:#ccc;">미지정</span>'; ?></td>
            <?php } ?>
            <td class="td_left" style="font-weight:bold;"><?php echo get_text($row['bt_name']); ?></td>
            <td class="td_left"><?php echo get_text($row['bt_location']); ?></td>
            <td><span style="<?php echo $status_cls; ?>"><?php echo $row['bt_status']; ?></span></td>
            <td><?php echo get_text($row['bt_manager_name']); ?></td>
            <td><?php echo get_text($row['bt_manager_hp']); ?></td>
            <td>
                <?php echo $row['bt_is_show'] ? '<span style="color:#2CC185;">● 노출</span>' : '<span style="color:#ccc;">● 미노출</span>'; ?>
            </td>
            <td class="td_mng td_mng_s">
                <button type="button" onclick="rainCopyUrl('<?php echo $pos_url; ?>')" class="btn btn_03" style="background:#009688; border-color:#009688; color:#fff;">URL 복사</button>
                <a href="./rain_booth_form.php?w=u&amp;bt_id=<?php echo $row['bt_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">수정</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="'.($is_admin == 'super' ? '9' : '8').'" class="empty_table">등록된 체험 부스가 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_booth_form.php" class="btn btn_01">+ 체험부스 등록</a>
</div>

<script>
function rainCopyUrl(url) {
    var tempInput = document.createElement("textarea");
    document.body.appendChild(tempInput);
    tempInput.value = url;
    tempInput.select();
    tempInput.setSelectionRange(0, 9999); /* 모바일 대응 */
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    
    alert("담당자에게 전달할 접속 URL이 복사되었습니다.\n\n" + url);
}
</script>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>
<?php include_once('./admin.tail.php'); ?>