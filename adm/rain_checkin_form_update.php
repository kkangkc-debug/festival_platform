<?php
$sub_menu = "800200";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// [중요] MY_FS_ID 가 세팅되어 있지 않거나 0 미만이면 비정상 접근 차단
if (!defined('MY_FS_ID') || MY_FS_ID < 0) {
    alert('행사 권한 정보가 유효하지 않습니다.');
}

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$ci_id = isset($_POST['ci_id']) ? (int)$_POST['ci_id'] : 0;

// 수신 데이터 정제
$ci_name = isset($_POST['ci_name']) ? clean_xss_tags(trim($_POST['ci_name'])) : '';
$ci_location = isset($_POST['ci_location']) ? clean_xss_tags(trim($_POST['ci_location'])) : '';
$ci_manager_name = isset($_POST['ci_manager_name']) ? clean_xss_tags(trim($_POST['ci_manager_name'])) : '';
$ci_manager_hp = isset($_POST['ci_manager_hp']) ? clean_xss_tags(trim($_POST['ci_manager_hp'])) : '';
$ci_device_id = isset($_POST['ci_device_id']) ? clean_xss_tags(trim($_POST['ci_device_id'])) : '';
$ci_device_uuid = isset($_POST['ci_device_uuid']) ? clean_xss_tags(trim($_POST['ci_device_uuid'])) : '';
$ci_status = isset($_POST['ci_status']) ? clean_xss_tags($_POST['ci_status']) : '운영';
$ci_is_show = isset($_POST['ci_is_show']) ? (int)$_POST['ci_is_show'] : 1;
$ci_memo = isset($_POST['ci_memo']) ? clean_xss_tags($_POST['ci_memo']) : '';

if (!$ci_name) alert('체크인존 명을 입력해 주세요.');
if (!$ci_location) alert('위치를 입력해 주세요.');

// [SaaS 핵심] 최고관리자(Lv.10)는 폼에서 넘어온 fs_id를 사용하고, 행사관리자(Lv.8)는 자신의 MY_FS_ID를 강제 주입합니다.
$target_fs_id = ($is_admin == 'super' && isset($_POST['fs_id'])) ? (int)$_POST['fs_id'] : MY_FS_ID;

$sql_common = " fs_id = '{$target_fs_id}',
                ci_name = '{$ci_name}',
                ci_location = '{$ci_location}',
                ci_manager_name = '{$ci_manager_name}',
                ci_manager_hp = '{$ci_manager_hp}',
                ci_device_id = '{$ci_device_id}',
                ci_device_uuid = '{$ci_device_uuid}',
                ci_status = '{$ci_status}',
                ci_is_show = '{$ci_is_show}',
                ci_memo = '{$ci_memo}' ";

if ($w == '') {
    // 신규 등록
    $sql = " INSERT INTO rain_checkin_info
                SET $sql_common,
                    mb_id = '{$member['mb_id']}',
                    ci_datetime = '".G5_TIME_YMDHIS."' ";
    sql_query($sql);
} else if ($w == 'u') {
    // [보안] 행사관리자는 자신의 행사에 속한 데이터만 수정할 수 있도록 검증
    if (MY_FS_ID > 0) {
        $chk = sql_fetch(" SELECT ci_id FROM rain_checkin_info WHERE ci_id = '{$ci_id}' AND fs_id = '".MY_FS_ID."' ");
        if (!$chk['ci_id']) alert('수정 권한이 없는 데이터입니다.');
    }

    // 수정 처리 (fs_id 포함 업데이트)
    $sql = " UPDATE rain_checkin_info
                SET $sql_common,
                    ci_mod_id = '{$member['mb_id']}',
                    ci_mod_datetime = '".G5_TIME_YMDHIS."'
              WHERE ci_id = '{$ci_id}' ";
    sql_query($sql);
} else {
    alert('잘못된 접근입니다.');
}

goto_url('./checkin_list.php');
?>