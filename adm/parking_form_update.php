<?php
$sub_menu = "800100";
include_once('./_common.php');
check_admin_token(); // 보안 토큰 검사

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$pi_id = isset($_POST['pi_id']) ? (int)$_POST['pi_id'] : 0;

// 입력값 정리
$pi_name = clean_xss_tags($_POST['pi_name']);
$pi_location = clean_xss_tags($_POST['pi_location']);
$pi_type_general = isset($_POST['pi_type_general']) ? 1 : 0;
$pi_type_barrier = isset($_POST['pi_type_barrier']) ? 1 : 0;
$pi_type_large = isset($_POST['pi_type_large']) ? 1 : 0;

$pi_capa_general = (int)$_POST['pi_capa_general'];
$pi_capa_pregnant = (int)$_POST['pi_capa_pregnant'];
$pi_capa_compact = (int)$_POST['pi_capa_compact'];
$pi_capa_eco = (int)$_POST['pi_capa_eco'];
$pi_capa_large = (int)$_POST['pi_capa_large'];

$pi_manager_name = clean_xss_tags($_POST['pi_manager_name']);
$pi_manager_hp = preg_replace('/[^0-9\-]/', '', $_POST['pi_manager_hp']); // 숫자와 하이픈만
$pi_status = clean_xss_tags($_POST['pi_status']);
$pi_is_show = (int)$_POST['pi_is_show'];
$pi_memo = clean_xss_tags($_POST['pi_memo']);

if (!$pi_type_general && !$pi_type_barrier && !$pi_type_large) {
    alert('주차장 유형을 1개 이상 선택해 주세요.');
}

$sql_common = " pi_name = '{$pi_name}',
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
    auth_check_menu($auth, $sub_menu, 'w');
    $sql = " insert into rain_park_info
                set $sql_common,
                    mb_id = '{$member['mb_id']}',
                    pi_datetime = '" . G5_TIME_YMDHIS . "',
                    pi_mod_id = '{$member['mb_id']}',
                    pi_mod_datetime = '" . G5_TIME_YMDHIS . "' ";
    sql_query($sql);
} else if ($w == 'u') {
    auth_check_menu($auth, $sub_menu, 'w');
    $sql = " update rain_park_info
                set $sql_common,
                    pi_mod_id = '{$member['mb_id']}',
                    pi_mod_datetime = '" . G5_TIME_YMDHIS . "'
              where pi_id = '{$pi_id}' ";
    sql_query($sql);
}

goto_url('./parking_list.php');
?>