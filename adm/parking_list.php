<?php
$sub_menu = "800100";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '주차장 관리';
include_once('./admin.head.php');

// 필터 및 검색 파라미터 초기화 (체크박스 다중 선택 배열)
$s_type = isset($_GET['s_type']) && is_array($_GET['s_type']) ? array_map('clean_xss_tags', $_GET['s_type']) : array();
$s_cong = isset($_GET['s_cong']) && is_array($_GET['s_cong']) ? array_map('clean_xss_tags', $_GET['s_cong']) : array();
$stx = isset($_GET['stx']) ? clean_xss_tags($_GET['stx']) : '';

$sql_search = " WHERE 1=1 ";

// =======================================================
// [SaaS 핵심 격리 로직]
// MY_FS_ID가 0보다 크면 무조건 자신의 행사 주차장만 보이게 강제 필터링
// =======================================================
if (defined('MY_FS_ID') && MY_FS_ID > 0) {
    $sql_search .= " AND fs_id = '" . MY_FS_ID . "' ";
}

if ($stx) {
    $sql_search .= " AND pi_name like '%{$stx}%' ";
}

// 주차 유형 (AND 검색: 체크한 유형을 모두 만족하는 데이터)
if (in_array('일반', $s_type)) {
    $sql_search .= " AND pi_type_general = 1 ";
}
if (in_array('베리어프리', $s_type)) {
    $sql_search .= " AND pi_type_barrier = 1 ";
}
if (in_array('대형', $s_type)) {
    $sql_search .= " AND pi_type_large = 1 ";
}

// 혼잡도 (OR 검색: 체크한 상태 중 하나라도 만족하는 데이터)
if (!empty($s_cong)) {
    $cong_queries = array();
    if (in_array('만차', $s_cong)) {
        $cong_queries[] = " remain <= 0 ";
    }
    if (in_array('혼잡', $s_cong)) {
        $cong_queries[] = " (remain > 0 and (remain / NULLIF(total_capa, 0) * 100) < 10) ";
    }
    if (in_array('보통', $s_cong)) {
        $cong_queries[] = " ((remain / NULLIF(total_capa, 0) * 100) >= 10 and (remain / NULLIF(total_capa, 0) * 100) < 50) ";
    }
    if (in_array('여유', $s_cong)) {
        $cong_queries[] = " ((remain / NULLIF(total_capa, 0) * 100) >= 50) ";
    }
    
    if (!empty($cong_queries)) {
        $sql_search .= " AND (" . implode(" OR ", $cong_queries) . ") ";
    }
}

// 쿼리 조립 (서브쿼리를 통해 동적 계산된 값을 필터링에 사용 + [수정] 행사명 조인)
$sql_common = " FROM (
    SELECT p.*, f.fs_name, 
           (p.pi_capa_general + p.pi_capa_pregnant + p.pi_capa_compact + p.pi_capa_eco + p.pi_capa_large) as total_capa,
           GREATEST(0, (p.pi_capa_general + p.pi_capa_pregnant + p.pi_capa_compact + p.pi_capa_eco + p.pi_capa_large) - p.pi_current_parked) as remain
    FROM rain_park_info p
    LEFT JOIN rain_festival f ON p.fs_id = f.fs_id
) a " . $sql_search;

// 페이징
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'] ?? 0;

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select * $sql_common order by pi_id desc limit $from_record, $rows ";
$result = sql_query($sql);

// 페이지 이동 시 검색어 유지를 위한 qstr 처리
foreach ($s_type as $val) {
    $qstr .= "&amp;s_type[]=".urlencode($val);
}
foreach ($s_cong as $val) {
    $qstr .= "&amp;s_cong[]=".urlencode($val);
}
?>

<div class="local_desc01 local_desc">
    <p><strong>[주차장 관리 목록 화면 이용 안내]</strong></p>
    <ul style="margin-top:10px; line-height:1.8em;">
        <li><strong>권한:</strong> 축제 운영 관리자 전용 메뉴이며, 소속된 행사의 주차장만 노출됩니다.</li>
        <li><strong>검색 및 필터:</strong> 주차 유형 및 혼잡도를 체크박스로 다중 선택(OR 검색)할 수 있습니다.</li>
        <li><strong>혼잡도 계산:</strong> (현재 잔여 대수 / 총 수용 대수) * 100
            <ul>
                <li>- <span style="color:#68d0a7; font-weight:bold;">여유</span>: 50% 이상 (초록색)</li>
                <li>- <span style="color:#ffa700; font-weight:bold;">보통</span>: 10% 이상 ~ 50% 미만 (노란색)</li>
                <li>- <span style="color:#fe528f; font-weight:bold;">혼잡</span>: 1% 이상 ~ 10% 미만 (빨간색)</li>
                <li>- <span style="color:#888; font-weight:bold;">만차</span>: 0% (회색)</li>
            </ul>
        </li>
    </ul>
</div>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 </span><span class="ov_num"> <?php echo number_format($total_count); ?>개 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    
    <div style="display:flex; gap:10px; align-items:center; border:1px solid #ccc; padding:0 10px; height:30px; border-radius:3px; background:#fff;">
        <strong style="color:#333;">주차 유형</strong>
        <label><input type="checkbox" name="s_type[]" value="일반" <?php echo in_array('일반', $s_type) ? 'checked' : ''; ?>> 일반</label>
        <label><input type="checkbox" name="s_type[]" value="베리어프리" <?php echo in_array('베리어프리', $s_type) ? 'checked' : ''; ?>> 베리어프리</label>
        <label><input type="checkbox" name="s_type[]" value="대형" <?php echo in_array('대형', $s_type) ? 'checked' : ''; ?>> 대형</label>
    </div>

    <div style="display:flex; gap:10px; align-items:center; border:1px solid #ccc; padding:0 10px; height:30px; border-radius:3px; background:#fff;">
        <strong style="color:#333;">혼잡도</strong>
        <label><input type="checkbox" name="s_cong[]" value="여유" <?php echo in_array('여유', $s_cong) ? 'checked' : ''; ?>> 여유</label>
        <label><input type="checkbox" name="s_cong[]" value="보통" <?php echo in_array('보통', $s_cong) ? 'checked' : ''; ?>> 보통</label>
        <label><input type="checkbox" name="s_cong[]" value="혼잡" <?php echo in_array('혼잡', $s_cong) ? 'checked' : ''; ?>> 혼잡</label>
        <label><input type="checkbox" name="s_cong[]" value="만차" <?php echo in_array('만차', $s_cong) ? 'checked' : ''; ?>> 만차</label>
    </div>

    <div style="display:flex; gap:5px; align-items:center;">
        <label for="stx" class="sound_only">주차장 명 검색</label>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" placeholder="주차장 명 검색" style="height:30px;">
        <button type="submit" class="btn_submit" title="검색">검색</button>
        <a href="./parking_list.php" class="btn btn_02" style="height:30px; line-height:30px; display:inline-block; border-radius:3px;">초기화</a>
    </div>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <?php if ($is_admin == 'super') { ?><th scope="col">소속 행사</th><?php } ?>
            <th scope="col">주차장 명</th>
            <th scope="col">위치</th>
            <th scope="col">주차 유형</th>
            <th scope="col">주차현황<br><span style="font-weight:normal; font-size:0.9em;">(잔여 대수 / 총 수용 대수)</span></th>
            <th scope="col">혼잡도</th>
            <th scope="col">담당자명</th>
            <th scope="col">사용자 노출</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            // 주차 유형 배열화
            $types = array();
            if($row['pi_type_general']) $types[] = '일반';
            if($row['pi_type_barrier']) $types[] = '베리어프리';
            if($row['pi_type_large']) $types[] = '대형';
            
            // 용량 계산 (서브쿼리에서 계산된 값 활용)
            $total_capa = $row['total_capa'];
            $remain = $row['remain'];
            $rate = ($total_capa > 0) ? ($remain / $total_capa) * 100 : 0;
            
            // 혼잡도 라벨 지정
            if($remain <= 0) { 
                $cong_txt = '만차'; 
                $cong_cls = 'background:#888; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;'; 
            }
            else if($rate < 10) { 
                $cong_txt = '혼잡'; 
                $cong_cls = 'background:#fe528f; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;'; 
            }
            else if($rate < 50) { 
                $cong_txt = '보통'; 
                $cong_cls = 'background:#ffa700; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;'; 
            }
            else { 
                $cong_txt = '여유'; 
                $cong_cls = 'background:#68d0a7; color:#fff; display:inline-block; padding:3px 10px; border-radius:3px; font-size:0.92em; font-weight:bold;'; 
            }

            // [추가] 소속 행사명 (미지정 처리)
            $display_fs_name = $row['fs_name'] ? get_text($row['fs_name']) : '<span style="color:#ccc;">미지정</span>';

            $bg = 'bg'.($i%2);
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - ($page - 1) * $rows - $i; ?></td>
            <?php if ($is_admin == 'super') { ?><td><?php echo $display_fs_name; ?></td><?php } ?>
            <td class="td_left"><?php echo get_text($row['pi_name']); ?></td>
            <td class="td_left"><?php echo get_text($row['pi_location']); ?></td>
            <td><?php echo implode('<br>', $types); ?></td>
            <td style="font-weight:bold;"><?php echo number_format($remain).' / '.number_format($total_capa); ?></td>
            <td><span style="<?php echo $cong_cls; ?>"><?php echo $cong_txt; ?></span></td>
            <td><?php echo get_text($row['pi_manager_name']); ?></td>
            <td>
                <?php if($row['pi_is_show']) { ?>
                    <span style="color:#2CC185; font-size:1.3em; vertical-align:middle;">●</span> 노출
                <?php } else { ?>
                    <span style="color:#ccc; font-size:1.3em; vertical-align:middle;">●</span> 미노출
                <?php } ?>
            </td>
            <td class="td_mng td_mng_s">
                <a href="./parking_view.php?pi_id=<?php echo $row['pi_id']; ?>&amp;<?php echo $qstr; ?>" class="btn btn_03">상세</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="'.($is_admin == 'super' ? '10' : '9').'" class="empty_table">등록된 주차장 자료가 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./parking_form.php" class="btn btn_01">+ 주차장 등록</a>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<?php include_once('./admin.tail.php'); ?>