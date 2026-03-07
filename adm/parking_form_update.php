<?php
$sub_menu = "800100";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// =======================================================
// [SaaS 핵심] 행사 권한 체크
// =======================================================
if (!defined('MY_FS_ID') || MY_FS_ID < 0) {
    alert('행사 권한 정보가 유효하지 않습니다.');
}

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$pi_id = isset($_POST['pi_id']) ? (int)$_POST['pi_id'] : 0;

// 파라미터 수신
$pi_name = isset($_POST['pi_name']) ? clean_xss_tags(trim($_POST['pi_name'])) : '';
$pi_location = isset($_POST['pi_location']) ? clean_xss_tags(trim($_POST['pi_location'])) : '';
$pi_manager_name = isset($_POST['pi_manager_name']) ? clean_xss_tags(trim($_POST['pi_manager_name'])) : '';
$pi_manager_hp = isset($_POST['pi_manager_hp']) ? clean_xss_tags(trim($_POST['pi_manager_hp'])) : '';
$pi_status = isset($_POST['pi_status']) ? clean_xss_tags($_POST['pi_status']) : '운영';
$pi_is_show = isset($_POST['pi_is_show']) ? (int)$_POST['pi_is_show'] : 1;
$pi_memo = isset($_POST['pi_memo']) ? clean_xss_tags($_POST['pi_memo']) : '';

$pi_type_general = isset($_POST['pi_type_general']) ? (int)$_POST['pi_type_general'] : 0;
$pi_type_barrier = isset($_POST['pi_type_barrier']) ? (int)$_POST['pi_type_barrier'] : 0;
$pi_type_large = isset($_POST['pi_type_large']) ? (int)$_POST['pi_type_large'] : 0;

$pi_capa_general = isset($_POST['pi_capa_general']) ? (int)$_POST['pi_capa_general'] : 0;
$pi_capa_pregnant = isset($_POST['pi_capa_pregnant']) ? (int)$_POST['pi_capa_pregnant'] : 0;
$pi_capa_compact = isset($_POST['pi_capa_compact']) ? (int)$_POST['pi_capa_compact'] : 0;
$pi_capa_eco = isset($_POST['pi_capa_eco']) ? (int)$_POST['pi_capa_eco'] : 0;
$pi_capa_large = isset($_POST['pi_capa_large']) ? (int)$_POST['pi_capa_large'] : 0;

if (!$pi_name) alert('주차장 명을 입력해 주세요.');
if (!$pi_location) alert('위치를 입력해 주세요.');

// [SaaS 격리] 최고관리자는 화면에서 넘어온 값 사용, 행사관리자는 무조건 자신의 세션값 강제 적용
$target_fs_id = ($is_admin == 'super' && isset($_POST['fs_id'])) ? (int)$_POST['fs_id'] : MY_FS_ID;

$sql_common = " fs_id = '{$target_fs_id}',
                pi_name = '{$pi_name}',
                pi_location = '{$pi_location}',
                pi_type_general = '{$pi_type_general}',
                pi_type_barrier = '{$pi_type_barrier}',
                pi_type_large = '{$pi_type_large}',
                pi_capa_general = '{$pi_capa_general}',
                pi_capa_pregnant = '{$pi_capa_pregnant}',
                pi_capa_compact = '{$pi_capa_compact}',
                pi_capa_eco = '{$pi_capa_eco}',
                pi_capa_large = '{$pi_capa_large}',
                pi_manager_name = '{$pi_manager_name}',
                pi_manager_hp = '{$pi_manager_hp}',
                pi_status = '{$pi_status}',
                pi_is_show = '{$pi_is_show}',
                pi_memo = '{$pi_memo}' ";

if ($w == '') {
    // 신규 등록
    $sql = " INSERT INTO rain_park_info
                SET $sql_common,
                    mb_id = '{$member['mb_id']}',
                    pi_datetime = '".G5_TIME_YMDHIS."' ";
    sql_query($sql);
} else if ($w == 'u') {
    // [SaaS 보안] 행사관리자는 남의 행사 데이터를 수정하지 못함
    if (MY_FS_ID > 0) {
        $chk = sql_fetch(" SELECT pi_id FROM rain_park_info WHERE pi_id = '{$pi_id}' AND fs_id = '".MY_FS_ID."' ");
        if (!$chk['pi_id']) alert('수정 권한이 없는 주차장 데이터입니다.');
    }

    $sql = " UPDATE rain_park_info
                SET $sql_common,
                    pi_mod_id = '{$member['mb_id']}',
                    pi_mod_datetime = '".G5_TIME_YMDHIS."'
              WHERE pi_id = '{$pi_id}' ";
    sql_query($sql);
}

goto_url('./parking_list.php');
?>