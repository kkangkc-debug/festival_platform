<?php
$sub_menu = "800600";
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if (!defined('MY_FS_ID') || MY_FS_ID < 0) alert('행사 권한 정보가 유효하지 않습니다.');

$w = isset($_POST['w']) ? clean_xss_tags($_POST['w']) : '';
$rt_id = isset($_POST['rt_id']) ? (int)$_POST['rt_id'] : 0;

$mb_id = isset($_POST['mb_id']) ? clean_xss_tags(trim($_POST['mb_id'])) : '';
$rt_name = isset($_POST['rt_name']) ? clean_xss_tags(trim($_POST['rt_name'])) : '';
$rt_type = isset($_POST['rt_type']) ? clean_xss_tags($_POST['rt_type']) : '푸드트럭';
$rt_location = isset($_POST['rt_location']) ? clean_xss_tags(trim($_POST['rt_location'])) : '';
$rt_manager_name = isset($_POST['rt_manager_name']) ? clean_xss_tags(trim($_POST['rt_manager_name'])) : '';
$rt_manager_hp = isset($_POST['rt_manager_hp']) ? clean_xss_tags(trim($_POST['rt_manager_hp'])) : '';
$rt_status = isset($_POST['rt_status']) ? clean_xss_tags($_POST['rt_status']) : '영업중';
$rt_is_show = isset($_POST['rt_is_show']) ? (int)$_POST['rt_is_show'] : 1;
$rt_memo = isset($_POST['rt_memo']) ? clean_xss_tags($_POST['rt_memo']) : '';

if (!$rt_name) alert('상점 명을 입력해 주세요.');

$target_fs_id = ($is_admin == 'super' && isset($_POST['fs_id'])) ? (int)$_POST['fs_id'] : MY_FS_ID;

$sql_common = " fs_id = '{$target_fs_id}',
                mb_id = '{$mb_id}',
                rt_name = '{$rt_name}',
                rt_type = '{$rt_type}',
                rt_location = '{$rt_location}',
                rt_manager_name = '{$rt_manager_name}',
                rt_manager_hp = '{$rt_manager_hp}',
                rt_status = '{$rt_status}',
                rt_is_show = '{$rt_is_show}',
                rt_memo = '{$rt_memo}' ";

if ($w == '') {
    $sql = " INSERT INTO rain_restaurant_info
                SET $sql_common,
                    reg_mb_id = '{$member['mb_id']}',
                    rt_datetime = '".G5_TIME_YMDHIS."' ";
    sql_query($sql);
    
} else if ($w == 'u') {
    if (MY_FS_ID > 0) {
        $chk = sql_fetch(" SELECT rt_id FROM rain_restaurant_info WHERE rt_id = '{$rt_id}' AND fs_id = '".MY_FS_ID."' ");
        if (!$chk['rt_id']) alert('수정 권한이 없는 데이터입니다.');
    }

    $sql = " UPDATE rain_restaurant_info
                SET $sql_common,
                    rt_mod_datetime = '".G5_TIME_YMDHIS."'
              WHERE rt_id = '{$rt_id}' ";
    sql_query($sql);
}

goto_url('./rain_restaurant_list.php');
?>