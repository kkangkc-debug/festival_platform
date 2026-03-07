<?php
$sub_menu = "800400";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if (!defined('MY_FS_ID') || MY_FS_ID < 0) alert('행사 권한 정보가 유효하지 않습니다.');

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$cp_id = isset($_POST['cp_id']) ? (int)$_POST['cp_id'] : 0;

$cp_name = isset($_POST['cp_name']) ? clean_xss_tags(trim($_POST['cp_name'])) : '';
$cp_type = isset($_POST['cp_type']) ? clean_xss_tags($_POST['cp_type']) : '금액할인';
$cp_amount = isset($_POST['cp_amount']) ? (int)$_POST['cp_amount'] : 0;
$cp_start_date = isset($_POST['cp_start_date']) ? clean_xss_tags($_POST['cp_start_date']) : '';
$cp_end_date = isset($_POST['cp_end_date']) ? clean_xss_tags($_POST['cp_end_date']) : '';
$cp_use_limit = isset($_POST['cp_use_limit']) ? (int)$_POST['cp_use_limit'] : 0;
$cp_status = isset($_POST['cp_status']) ? clean_xss_tags($_POST['cp_status']) : '대기';
$cp_memo = isset($_POST['cp_memo']) ? clean_xss_tags($_POST['cp_memo']) : '';

if (!$cp_name) alert('쿠폰 명을 입력해 주세요.');

$target_fs_id = ($is_admin == 'super' && isset($_POST['fs_id'])) ? (int)$_POST['fs_id'] : MY_FS_ID;

$sql_common = " fs_id = '{$target_fs_id}',
                cp_name = '{$cp_name}',
                cp_type = '{$cp_type}',
                cp_amount = '{$cp_amount}',
                cp_start_date = '{$cp_start_date}',
                cp_end_date = '{$cp_end_date}',
                cp_use_limit = '{$cp_use_limit}',
                cp_status = '{$cp_status}',
                cp_memo = '{$cp_memo}' ";

if ($w == '') {
    $sql = " INSERT INTO rain_coupon_info
                SET $sql_common,
                    mb_id = '{$member['mb_id']}',
                    cp_datetime = '".G5_TIME_YMDHIS."' ";
    sql_query($sql);
    
} else if ($w == 'u') {
    if (MY_FS_ID > 0) {
        $chk = sql_fetch(" SELECT cp_id FROM rain_coupon_info WHERE cp_id = '{$cp_id}' AND fs_id = '".MY_FS_ID."' ");
        if (!$chk['cp_id']) alert('수정 권한이 없는 데이터입니다.');
    }

    $sql = " UPDATE rain_coupon_info
                SET $sql_common,
                    cp_mod_id = '{$member['mb_id']}',
                    cp_mod_datetime = '".G5_TIME_YMDHIS."'
              WHERE cp_id = '{$cp_id}' ";
    sql_query($sql);
} else {
    alert('잘못된 접근입니다.');
}

goto_url('./rain_coupon_list.php');
?>