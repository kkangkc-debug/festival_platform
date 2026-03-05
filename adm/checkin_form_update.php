<?php
$sub_menu = "800200";
include_once('./_common.php');
check_admin_token();

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$ci_id = isset($_POST['ci_id']) ? (int)$_POST['ci_id'] : 0;

$ci_name = clean_xss_tags($_POST['ci_name']);
$ci_location = clean_xss_tags($_POST['ci_location']);
$ci_manager_name = clean_xss_tags($_POST['ci_manager_name']);
$ci_manager_hp = preg_replace('/[^0-9\-]/', '', $_POST['ci_manager_hp']);
$ci_device_id = clean_xss_tags($_POST['ci_device_id']);
$ci_device_uuid = clean_xss_tags($_POST['ci_device_uuid']);
$ci_status = clean_xss_tags($_POST['ci_status']);
$ci_is_show = (int)$_POST['ci_is_show'];
$ci_memo = clean_xss_tags($_POST['ci_memo']);

if(!$ci_name) alert('체크인존 명을 입력해 주세요.');
if(!$ci_manager_hp) alert('연락처는 숫자만 입력해 주세요.');

$sql_common = " ci_name = '{$ci_name}',
                ci_location = '{$ci_location}',
                ci_manager_name = '{$ci_manager_name}',
                ci_manager_hp = '{$ci_manager_hp}',
                ci_device_id = '{$ci_device_id}',
                ci_device_uuid = '{$ci_device_uuid}',
                ci_status = '{$ci_status}',
                ci_is_show = '{$ci_is_show}',
                ci_memo = '{$ci_memo}' ";

if ($w == '') {
    auth_check_menu($auth, $sub_menu, 'w');
    $sql = " insert into rain_checkin_info
                set $sql_common,
                    ci_sync_status = '오프라인',
                    mb_id = '{$member['mb_id']}',
                    ci_datetime = '" . G5_TIME_YMDHIS . "',
                    ci_mod_id = '{$member['mb_id']}',
                    ci_mod_datetime = '" . G5_TIME_YMDHIS . "' ";
    sql_query($sql);
} else if ($w == 'u') {
    auth_check_menu($auth, $sub_menu, 'w');
    $sql = " update rain_checkin_info
                set $sql_common,
                    ci_mod_id = '{$member['mb_id']}',
                    ci_mod_datetime = '" . G5_TIME_YMDHIS . "'
              where ci_id = '{$ci_id}' ";
    sql_query($sql);
}

goto_url('./checkin_list.php');
?>