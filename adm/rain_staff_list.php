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
            
            // 역할 및 타겟 이름 가져오기
            $role_name = '미지정';
            $target_name = '미지정';
            
            if ($row['role_type'] == 'PARKING_STAFF') {
                $role_name = '<span style="color:#3f51b5; font-weight:bold;">[주차장 스태프]</span>';
                $pi = sql_fetch("SELECT pi_name FROM rain_park_info WHERE pi_id = '{$row['target_id']}'");
                $target_name = $pi['pi_name'] ?? '삭제된 주차장';
            } else if ($row['role_type'] == 'BOOTH_STAFF') {
                $role_name = '<span style="color:#ff3061; font-weight:bold;">[체험부스 스태프]</span>';
                $bt = sql_fetch("SELECT bt_name FROM rain_booth_info WHERE bt_id = '{$row['target_id']}'");
                $target_name = $bt['bt_name'] ?? '삭제된 부스';
            }
        ?>
        <tr class="<?php echo $bg; ?>">
            <td><?php echo $total_count - $i; ?></td>
            <?php if ($is_admin == 'super') { ?><td><?php echo get_text($row['fs_name']); ?></td><?php } ?>
            <td><strong><?php echo $row['mb_id']; ?></strong> (<?php echo $row['mb_name']; ?>)</td>
            <td><?php echo $role_name; ?></td>
            <td style="font-weight:bold;"><?php echo $target_name; ?></td>
            
            <td><?php echo isset($row['fm_datetime']) ? $row['fm_datetime'] : '-'; ?></td>
            
            <td class="td_mng td_mng_s">
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

<?php include_once('./admin.tail.php'); ?>