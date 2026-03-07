<?php
$sub_menu = "800850"; // 현장 스태프 배정 메뉴 코드
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '현장 스태프 관리';
include_once('./admin.head.php');

$sql_search = " WHERE 1=1 ";

// [SaaS 핵심 격리] 내 행사의 스태프만 보기
if (defined('MY_FS_ID') && MY_FS_ID > 0) {
    $sql_search .= " AND m.fs_id = '" . MY_FS_ID . "' ";
}

$sql_common = " FROM rain_festival_manager m 
                LEFT JOIN {$g5['member_table']} mb ON m.mb_id = mb.mb_id 
                LEFT JOIN rain_festival f ON m.fs_id = f.fs_id " . $sql_search;

$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'] ?? 0;

$sql = " select m.*, mb.mb_name, f.fs_name $sql_common order by m.fm_id desc ";
$result = sql_query($sql);
?>

<div class="local_desc01 local_desc">
    <p><strong>[현장 스태프 권한 배정 안내]</strong></p>
    <ul>
        <li>현장 알바생(스태프)들에게 특정 주차장이나 체험부스를 지정해 주어야 해당 POS 화면에 접근할 수 있습니다.</li>
        <li><strong>URL 복사</strong> 버튼을 눌러 담당자에게 해당 구역의 모바일 관리자 페이지 주소를 카톡이나 문자로 전달해 주세요.</li>
    </ul>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
        <tr>
            <th scope="col">번호</th>
            <?php if ($is_admin == 'super') { ?><th scope="col">소속 행사</th><?php } ?>
            <th scope="col">스태프 아이디 (이름)</th>
            <th scope="col">역할 (담당 업무)</th>
            <th scope="col">배정된 구역 (대상)</th>
            <th scope="col">배정일시</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $bg = 'bg'.($i%2);
            
            // =======================================================
            // [수정된 부분] 역할 및 타겟 이름 예외 처리 강화 + URL 세팅
            // =======================================================
            $role_name = '';
            $target_name = '';
            $pos_url = ''; // 스태프에게 전달할 URL 변수 추가
            
            if ($row['role_type'] == 'PARKING_STAFF') {
                $role_name = '<span style="color:#3f51b5; font-weight:bold;">[주차장 스태프]</span>';
                $pi = sql_fetch("SELECT pi_name FROM rain_park_info WHERE pi_id = '{$row['target_id']}'");
                $target_name = isset($pi['pi_name']) ? get_text($pi['pi_name']) : '<span style="color:#aaa;">(삭제된 주차장)</span>';
                $pos_url = G5_ADMIN_URL.'/rain_parking_staff_pos.php'; // 주차장 전용 접속 주소
            
            } else if ($row['role_type'] == 'BOOTH_STAFF') {
                $role_name = '<span style="color:#ff3061; font-weight:bold;">[체험부스 스태프]</span>';
                $bt = sql_fetch("SELECT bt_name FROM rain_booth_info WHERE bt_id = '{$row['target_id']}'");
                $target_name = isset($bt['bt_name']) ? get_text($bt['bt_name']) : '<span style="color:#aaa;">(삭제된 부스)</span>';
                $pos_url = G5_ADMIN_URL.'/rain_booth_staff_pos.php'; // 부스 전용 접속 주소
            
            } else {
                // DB에 '총괄관리자' 등 지정되지 않은 텍스트가 들어있을 경우의 방어 코드
                $role_name = '<span style="color:#2CC185; font-weight:bold;">[' . get_text($row['role_type']) . ']</span>';
                
                // 타겟 ID가 0인 경우(전체 권한) 처리
                if ($row['target_id'] == 0) {
                    $target_name = '<span style="color:#888;">전체 (특정 대상 없음)</span>';
                } else {
                    $target_name = 'ID: ' . $row['target_id'] . ' (기타)';
                }
                $pos_url = G5_ADMIN_URL; // 총괄관리자는 기본 관리자 메인 주소
            }
            // =======================================================
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - $i; ?></td>
            <?php if ($is_admin == 'super') { ?><td><?php echo get_text($row['fs_name']); ?></td><?php } ?>
            <td><strong><?php echo $row['mb_id']; ?></strong> (<?php echo $row['mb_name']; ?>)</td>
            <td><?php echo $role_name; ?></td>
            <td style="font-weight:bold;"><?php echo $target_name; ?></td>
            
            <td><?php echo isset($row['fm_datetime']) && $row['fm_datetime'] != '0000-00-00 00:00:00' ? $row['fm_datetime'] : '-'; ?></td>
            
            <td class="td_mng td_mng_s">
                <button type="button" onclick="rainCopyUrl('<?php echo $pos_url; ?>')" class="btn btn_03" style="background:#009688; border-color:#009688; color:#fff;">URL 복사</button>
                <a href="./rain_staff_form_update.php?w=d&amp;fm_id=<?php echo $row['fm_id']; ?>" onclick="return confirm('이 스태프의 배정을 해제하시겠습니까?');" class="btn btn_02">배정 해제</a>
            </td>
        </tr>
        <?php } if ($i == 0) { echo '<tr><td colspan="'.($is_admin == 'super' ? '7' : '6').'" class="empty_table">배정된 현장 스태프가 없습니다.</td></tr>'; } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./rain_staff_form.php" class="btn btn_01">+ 현장 스태프 배정하기</a>
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

<?php include_once('./admin.tail.php'); ?>