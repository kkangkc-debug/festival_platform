<?php
$sub_menu = "800850"; // 현장 스태프 배정 메뉴 코드
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// [SaaS 보안] 최고관리자가 아니면 무조건 자신의 행사 ID로 고정
if ($is_admin != 'super') {
    if (!defined('MY_FS_ID') || MY_FS_ID < 1) {
        alert('행사 권한 정보가 유효하지 않습니다.');
    }
    $target_fs_id = MY_FS_ID;
} else {
    // 최고관리자는 폼에서 선택한 행사 ID를 따름
    $target_fs_id = isset($_POST['fs_id']) ? (int)$_POST['fs_id'] : 0;
}

// 동작(신규/삭제) 및 고유번호 가져오기
$w = isset($_REQUEST['w']) ? clean_xss_tags($_REQUEST['w']) : '';
$fm_id = isset($_REQUEST['fm_id']) ? (int)$_REQUEST['fm_id'] : 0;

if ($w == '') {
    // ===================================================================
    // 1. 신규 스태프 배정 (INSERT)
    // ===================================================================
    $mb_id = isset($_POST['mb_id']) ? clean_xss_tags(trim($_POST['mb_id'])) : '';
    $role_target = isset($_POST['role_target']) ? clean_xss_tags($_POST['role_target']) : '';
    
    if (!$mb_id) alert('스태프 아이디를 선택해 주세요.');
    if (!$role_target) alert('배정할 구역을 선택해 주세요.');
    
    // 폼에서 넘어온 "역할|타겟ID" 값을 분리 (예: PARKING_STAFF|1)
    $exp = explode('|', $role_target);
    $role_type = isset($exp[0]) ? $exp[0] : '';
    $target_id = isset($exp[1]) ? (int)$exp[1] : 0;
    
    if (!$role_type || !$target_id) alert('잘못된 배정 정보입니다.');

    // [중복 방지] 이미 동일한 스태프가 동일한 구역에 배정되어 있는지 확인
    $chk = sql_fetch(" SELECT count(*) as cnt FROM rain_festival_manager WHERE mb_id = '{$mb_id}' AND role_type = '{$role_type}' AND target_id = '{$target_id}' ");
    if ($chk['cnt'] > 0) alert('해당 스태프는 이미 이 구역에 배정되어 있습니다.');

    // DB 저장
    $sql = " INSERT INTO rain_festival_manager
                SET fs_id = '{$target_fs_id}',
                    mb_id = '{$mb_id}',
                    role_type = '{$role_type}',
                    target_id = '{$target_id}',
                    fm_datetime = '".G5_TIME_YMDHIS."' ";
    sql_query($sql);

    alert('스태프 배정이 완료되었습니다.', './rain_staff_list.php');

} else if ($w == 'd') {
    // ===================================================================
    // 2. 배정 해제 / 삭제 (DELETE)
    // ===================================================================
    if (!$fm_id) alert('잘못된 접근입니다.');

    // [보안] 행사관리자(Lv.8)는 자신의 행사에 속한 배정 내역만 삭제 가능
    if ($is_admin != 'super') {
        $chk = sql_fetch(" SELECT fm_id FROM rain_festival_manager WHERE fm_id = '{$fm_id}' AND fs_id = '{$target_fs_id}' ");
        if (!$chk['fm_id']) alert('삭제 권한이 없습니다.');
    }

    // DB 삭제
    sql_query(" DELETE FROM rain_festival_manager WHERE fm_id = '{$fm_id}' ");
    
    alert('배정이 해제되었습니다.', './rain_staff_list.php');
    
} else {
    alert('올바른 방법으로 이용해 주세요.');
}
?>