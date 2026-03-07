<?php
$sub_menu = "800300"; // 체험부스 관리 메뉴 코드
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// [중요] MY_FS_ID 가 세팅되어 있지 않거나 0 미만이면 비정상 접근 차단
if (!defined('MY_FS_ID') || MY_FS_ID < 0) {
    alert('행사 권한 정보가 유효하지 않습니다.');
}

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$bt_id = isset($_POST['bt_id']) ? (int)$_POST['bt_id'] : 0;

// 수신 데이터 정제 (XSS 방어 및 공백 제거)
$bt_name = isset($_POST['bt_name']) ? clean_xss_tags(trim($_POST['bt_name'])) : '';
$bt_location = isset($_POST['bt_location']) ? clean_xss_tags(trim($_POST['bt_location'])) : '';
$bt_manager_name = isset($_POST['bt_manager_name']) ? clean_xss_tags(trim($_POST['bt_manager_name'])) : '';
$bt_manager_hp = isset($_POST['bt_manager_hp']) ? clean_xss_tags(trim($_POST['bt_manager_hp'])) : '';
$bt_status = isset($_POST['bt_status']) ? clean_xss_tags($_POST['bt_status']) : '운영';
$bt_is_show = isset($_POST['bt_is_show']) ? (int)$_POST['bt_is_show'] : 1;
$bt_memo = isset($_POST['bt_memo']) ? clean_xss_tags($_POST['bt_memo']) : '';

// 필수 값 체크
if (!$bt_name) alert('부스 명을 입력해 주세요.');
if (!$bt_location) alert('부스 위치/구역을 입력해 주세요.');

// [SaaS 핵심] 최고관리자(Lv.10)는 폼에서 넘어온 fs_id를 사용하고, 행사관리자(Lv.8)는 자신의 MY_FS_ID를 강제 주입합니다.
$target_fs_id = ($is_admin == 'super' && isset($_POST['fs_id'])) ? (int)$_POST['fs_id'] : MY_FS_ID;

// 공통 업데이트 쿼리문 조립
$sql_common = " fs_id = '{$target_fs_id}',
                bt_name = '{$bt_name}',
                bt_location = '{$bt_location}',
                bt_manager_name = '{$bt_manager_name}',
                bt_manager_hp = '{$bt_manager_hp}',
                bt_status = '{$bt_status}',
                bt_is_show = '{$bt_is_show}',
                bt_memo = '{$bt_memo}' ";

if ($w == '') {
    // 신규 부스 등록
    $sql = " INSERT INTO rain_booth_info
                SET $sql_common,
                    mb_id = '{$member['mb_id']}',
                    bt_datetime = '".G5_TIME_YMDHIS."' ";
    sql_query($sql);
    
} else if ($w == 'u') {
    // [보안] 행사관리자는 자신의 행사에 속한 데이터만 수정할 수 있도록 검증
    if (MY_FS_ID > 0) {
        $chk = sql_fetch(" SELECT bt_id FROM rain_booth_info WHERE bt_id = '{$bt_id}' AND fs_id = '".MY_FS_ID."' ");
        if (!$chk['bt_id']) alert('수정 권한이 없는 부스 데이터입니다.');
    }

    // 부스 정보 수정
    $sql = " UPDATE rain_booth_info
                SET $sql_common,
                    bt_mod_id = '{$member['mb_id']}',
                    bt_mod_datetime = '".G5_TIME_YMDHIS."'
              WHERE bt_id = '{$bt_id}' ";
    sql_query($sql);
    
} else {
    alert('잘못된 접근입니다.');
}

// 처리 완료 후 목록 페이지로 이동
goto_url('./rain_booth_list.php');
?>