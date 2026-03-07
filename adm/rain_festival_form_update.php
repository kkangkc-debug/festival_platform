<?php
$sub_menu = "900100";
include_once('./_common.php');

if ($w == 'u')
    auth_check_menu($auth, $sub_menu, 'w');
else
    auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

$fs_name = isset($_POST['fs_name']) ? clean_xss_tags(trim($_POST['fs_name'])) : '';
$fs_start_date = isset($_POST['fs_start_date']) ? clean_xss_tags(trim($_POST['fs_start_date'])) : '';
$fs_end_date = isset($_POST['fs_end_date']) ? clean_xss_tags(trim($_POST['fs_end_date'])) : '';
$fs_status = isset($_POST['fs_status']) ? clean_xss_tags(trim($_POST['fs_status'])) : '';

if (!$fs_name) alert('행사명은 필수입니다.');
if (!$fs_start_date || !$fs_end_date) alert('행사 기간은 필수입니다.');

$sql_common = " SET fs_name = '{$fs_name}',
                    fs_start_date = '{$fs_start_date}',
                    fs_end_date = '{$fs_end_date}',
                    fs_status = '{$fs_status}' ";

if ($w == '') {
    // 새 행사 개설
    $sql = " INSERT INTO rain_festival
                $sql_common ,
                mb_id = '{$member['mb_id']}',
                fs_datetime = '".G5_TIME_YMDHIS."' ";
    sql_query($sql);
    $fs_id = sql_insert_id();
    // 필요 시 개설 직후 알림 로직 추가 가능
} else if ($w == 'u') {
    // 정보 수정
    // [수정] $POST['fs_id'] 를 $_POST['fs_id'] 로 변경
    $fs_id = (int)$_POST['fs_id']; 
    
    $sql = " UPDATE rain_festival
                $sql_common
                WHERE fs_id = '{$fs_id}' ";
    sql_query($sql);
} else {
    alert('잘못된 접근입니다.');
}

goto_url('./rain_festival_list.php?'.$qstr);
?>