<?php
$sub_menu = "800200";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '체크인존 관리';
include_once('./admin.head.php');

// 필터 및 검색 파라미터 초기화 (체크박스 다중 선택을 위해 배열로 처리)
$s_sync = isset($_GET['s_sync']) && is_array($_GET['s_sync']) ? array_map('clean_xss_tags', $_GET['s_sync']) : array();
$s_status = isset($_GET['s_status']) && is_array($_GET['s_status']) ? array_map('clean_xss_tags', $_GET['s_status']) : array();
$stx = isset($_GET['stx']) ? clean_xss_tags($_GET['stx']) : '';

$sql_search = " WHERE 1=1 ";

// =======================================================
// [SaaS 핵심 격리 로직]
// MY_FS_ID가 0보다 크면 무조건 자신의 행사 체크인존만 보이게 강제 필터링
// =======================================================
if (defined('MY_FS_ID') && MY_FS_ID > 0) {
    $sql_search .= " AND c.fs_id = '" . MY_FS_ID . "' ";
}

// 텍스트 검색
if ($stx) {
    $sql_search .= " and c.ci_name like '%{$stx}%' ";
}

// 연동 상태 다중 필터 (선택된 값 중 하나라도 포함되면 노출)
if (!empty($s_sync)) {
    $sync_str = implode("','", $s_sync);
    $sql_search .= " and c.ci_sync_status IN ('{$sync_str}') ";
}

// 운영 설정 다중 필터 (선택된 값 중 하나라도 포함되면 노출)
if (!empty($s_status)) {
    $status_str = implode("','", $s_status);
    $sql_search .= " and c.ci_status IN ('{$status_str}') ";
}

// [수정] rain_festival 테이블과 조인하여 행사명(fs_name)을 가져옵니다.
$sql_common = " from rain_checkin_info c left join rain_festival f on c.fs_id = f.fs_id " . $sql_search;

$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'] ?? 0;

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

// [수정] 조인된 필드를 포함하여 데이터를 가져옵니다.
$sql = " select c.*, f.fs_name $sql_common order by c.ci_id desc limit $from_record, $rows ";
$result = sql_query($sql);

// 페이지 이동 시 검색어 유지를 위한 qstr 처리
foreach ($s_sync as $val) {
    $qstr .= "&amp;s_sync[]=".urlencode($val);
}
foreach ($s_status as $val) {
    $qstr .= "&amp;s_status[]=".urlencode($val);
}
?>

<div class="local_desc01 local_desc">
    <p><strong>[체크인존 관리 목록 화면 이용 안내]</strong></p>
    <ul style="margin-top:10px; line-height:1.8em;">
        <li><strong>권한:</strong> 축제 운영 관리자 전용 메뉴입니다. 소속된 행사의 체크인존만 노출됩니다.</li>
        <li><strong>검색 및 필터:</strong> 연동 상태 및 운영 설정을 체크박스로 다중 선택하여 조건에 맞는 목록을 조회할 수 있습니다.</li>
        <li><strong>당일 입장객 수:</strong> 해당 체크인존을 통해 입장 완료 처리된 실시간 인원 합계입니다.</li>
    </ul>
</div>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    
    <div style="display:flex; gap:10px; align-items:center; border:1px solid #ccc; padding:0 10px; height:30px; border-radius:3px; background:#fff;">
        <strong style="color:#333;">연동 상태</strong>
        <label><input type="checkbox" name="s_sync[]" value="정상" <?php echo in_array('정상', $s_sync) ? 'checked' : ''; ?>> 정상</label>
        <label><input type="checkbox" name="s_sync[]" value="장애" <?php echo in_array('장애', $s_sync) ? 'checked' : ''; ?>> 장애</label>
        <label><input type="checkbox" name="s_sync[]" value="오프라인" <?php echo in_array('오프라인', $s_sync) ? 'checked' : ''; ?>> 오프라인</label>
    </div>

    <div style="display:flex; gap:10px; align-items:center; border:1px solid #ccc; padding:0 10px; height:30px; border-radius:3px; background:#fff;">
        <strong style="color:#333;">운영 설정</strong>
        <label><input type="checkbox" name="s_status[]" value="운영" <?php echo in_array('운영', $s_status) ? 'checked' : ''; ?>> 운영</label>
        <label><input type="checkbox" name="s_status[]" value="장애" <?php echo in_array('장애', $s_status) ? 'checked' : ''; ?>> 장애</label>
        <label><input type="checkbox" name="s_status[]" value="마감" <?php echo in_array('마감', $s_status) ? 'checked' : ''; ?>> 마감</label>
    </div>

    <div style="display:flex; gap:5px; align-items:center;">
        <label for="stx" class="sound_only">체크인존 명 검색</label>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="체크인존 명 검색" style="height:30px;">
        <button type="submit" class="btn_submit" title="검색">검색</button>
        <a href="./checkin_list.php" class="btn btn_02" style="height:30px; line-height:30px; display:inline-block; border-radius:3px;">초기화</a>
    </div>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <?php if ($is_admin == 'super') { ?>
            <th scope="col">소속 행사</th>
            <?php } ?>
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
            
            if($row['ci_sync_status'] == '장애') {
                $sync_cls = 'background:#fe528f; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;';
            } else if($row['ci_sync_status'] == '정상') {
                $sync_cls = 'background:#68d0a7; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;';
            } else {
                $sync_cls = 'background:#888; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;';
            }
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <?php if ($is_admin == 'super') { ?>
            <td><?php echo $row['fs_name'] ? get_text($row['fs_name']) : '<span style="color:#ccc;">미지정</span>'; ?></td>
            <?php } ?>
            <td class="td_left" style="font-weight:bold;"><?php echo get_text($row['ci_name']); ?></td>
            <td class="td_left"><?php echo get_text($row['ci_location']); ?></td>
            <td><span style="<?php echo $sync_cls; ?>"><?php echo $row['ci_sync_status']; ?></span></td>
            <td><strong><?php echo number_format($row['ci_today_visitors']); ?></strong></td>
            <td><?php echo $row['ci_status']; ?></td>
            <td><?php echo get_text($row['ci_manager_name']); ?></td>
            <td>
                <?php if($row['ci_is_show']) { ?>
                    <span style="color:#2CC185; font-size:1.3em; vertical-align:middle;">●</span> 노출
                <?php } else { ?>
                    <span style="color:#ccc; font-size:1.3em; vertical-align:middle;">●</span> 미노출
                <?php } ?>
            </td>
            <td class="td_mng td_mng_s">
                <a href="./rain_checkin_view.php?ci_id=<?php echo $row['ci_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">상세</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="'.($is_admin == 'super' ? '10' : '9').'" class="empty_table">등록된 체크인존 자료가 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_checkin_form.php" class="btn btn_01">+ 체크인존 등록</a>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<?php include_once('./admin.tail.php'); ?>